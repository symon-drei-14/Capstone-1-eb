<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';
// Get all maintenance records with pagination
function getMaintenanceRecords($conn, $page = 1, $rowsPerPage = 5, $statusFilter = 'all', $showDeleted = false) {
    // First, update any pending records that are now overdue
    $updateOverdue = $conn->prepare("UPDATE maintenance_table m SET status = 'Overdue' 
                                   WHERE status IN ('Pending', 'In Progress') 
                                   AND date_mtnce < CURDATE()
                                   AND NOT EXISTS (
                                       SELECT 1 FROM audit_logs_maintenance al 
                                       WHERE al.maintenance_id = m.maintenance_id 
                                       AND al.is_deleted = 1
                                   )");
    
    if (!$updateOverdue) {
        error_log("Failed to prepare updateOverdue query: " . $conn->error);
    } else {
        $updateOverdue->execute();
    }
    
    // Then update truck statuses for any newly overdue maintenance
    $getOverdueTrucks = $conn->query("SELECT DISTINCT m.truck_id, t.plate_no 
                                    FROM maintenance_table m
                                    JOIN truck_table t ON m.truck_id = t.truck_id
                                    WHERE m.status = 'Overdue' 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM audit_logs_maintenance al 
                                        WHERE al.maintenance_id = m.maintenance_id 
                                        AND al.is_deleted = 1
                                    )
                                    AND NOT EXISTS (
                                        SELECT 1 FROM audit_logs_trucks alt 
                                        WHERE alt.truck_id = t.truck_id 
                                        AND alt.is_deleted = 1
                                    )");
    
    if ($getOverdueTrucks) {
        while ($row = $getOverdueTrucks->fetch_assoc()) {
            // Update truck status to Overdue
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Overdue' 
                                         WHERE truck_id = ?");
            if ($updateTruck) {
                $updateTruck->bind_param("i", $row['truck_id']);
                $updateTruck->execute();
            }
        }
    }

    $offset = ($page - 1) * $rowsPerPage;
    
$sql = "SELECT 
    m.maintenance_id AS maintenanceId,
    m.truck_id AS truckId,
    t.plate_no AS licensePlate,
    m.date_mtnce AS maintenanceDate,
    m.remarks,
    m.status,
    s.name AS supplierName,
    m.supplier_id AS supplierId,
    mt.type_name AS maintenanceTypeName,
    m.maintenance_type_id AS maintenanceTypeId,
    m.cost,
    al.is_deleted AS isDeleted,
    al.delete_reason AS deleteReason,
    al.modified_by AS lastUpdatedBy,
    al.modified_at AS lastUpdatedAt,
    al.edit_reason AS editReason
FROM maintenance_table m
LEFT JOIN truck_table t 
    ON m.truck_id = t.truck_id
LEFT JOIN maintenance_types mt 
    ON m.maintenance_type_id = mt.maintenance_type_id
LEFT JOIN suppliers s 
    ON m.supplier_id = s.supplier_id
LEFT JOIN (
    SELECT 
        maintenance_id,
        modified_by,
        modified_at,
        edit_reason,
        is_deleted,
        delete_reason,
        ROW_NUMBER() OVER (PARTITION BY maintenance_id ORDER BY modified_at DESC) AS rn
    FROM audit_logs_maintenance
) al 
    ON m.maintenance_id = al.maintenance_id 
   AND al.rn = 1";
    
    $params = [];
    $types = '';
    $whereClauses = [];
    
    if ($statusFilter !== 'all') {
        $whereClauses[] = "m.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    
    // Handle deleted records filter
// Handle deleted records filter
if ($showDeleted) {
    $whereClauses[] = "COALESCE(al.is_deleted, 0) = 1";
} else {
    $whereClauses[] = "COALESCE(al.is_deleted, 0) = 0";
}

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
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
    $countSql = "SELECT COUNT(*) as total FROM maintenance_table m";
    $countParams = [];
    $countTypes = '';
    $countWhereClauses = [];
    
    if ($statusFilter !== 'all') {
        $countWhereClauses[] = "m.status = ?";
        $countParams[] = $statusFilter;
        $countTypes .= "s";
    }
    
if ($showDeleted) {
    $countWhereClauses[] = "EXISTS (
        SELECT 1 FROM audit_logs_maintenance al 
        WHERE al.maintenance_id = m.maintenance_id 
          AND al.is_deleted = 1
    )";
} else {
    $countWhereClauses[] = "NOT EXISTS (
        SELECT 1 FROM audit_logs_maintenance al 
        WHERE al.maintenance_id = m.maintenance_id 
          AND al.is_deleted = 1
    )";
}

    
    if (!empty($countWhereClauses)) {
        $countSql .= " WHERE " . implode(" AND ", $countWhereClauses);
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
        "currentPage" => $page,
        "totalRecords" => $totalRows 
    ];
}

function getMaintenanceCounts($conn) {
    $sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN m.status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN m.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN m.status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN m.status = 'Overdue' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN m.status = 'Completed' 
             AND MONTH(m.date_mtnce) = MONTH(CURRENT_DATE()) 
             AND YEAR(m.date_mtnce) = YEAR(CURRENT_DATE()) 
        THEN 1 ELSE 0 END) as completed_this_month
FROM maintenance_table m
WHERE NOT EXISTS (
    SELECT 1
    FROM audit_logs_maintenance a
    WHERE a.maintenance_id = m.maintenance_id
      AND a.is_deleted = 1
      AND a.modified_at = (
            SELECT MAX(a2.modified_at)
            FROM audit_logs_maintenance a2
            WHERE a2.maintenance_id = m.maintenance_id
      )
);";
    
    $result = $conn->query($sql);
    if (!$result) {
    die("SQL Error in getMaintenanceCounts: " . $conn->error . " | SQL: " . $sql);
}
    return $result->fetch_assoc();
}

// Get maintenance history for a specific truck
function getMaintenanceHistory($conn, $truckId) {
    $sql = "SELECT m.maintenance_id, m.date_mtnce, m.remarks, m.status, m.cost, 
        mt.type_name as maintenance_type_name, s.name as supplier_name,
        al.modified_by as last_modified_by, al.modified_at as last_modified_at, 
        t.plate_no as licence_plate
        FROM maintenance_table m
        LEFT JOIN truck_table t ON m.truck_id = t.truck_id
        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
        LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
        LEFT JOIN audit_logs_maintenance al ON m.maintenance_id = al.maintenance_id
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
    // Debug: Check what records exist
    $debugSql = "SELECT COUNT(*) as total_records FROM maintenance_table m LEFT JOIN truck_table t ON m.truck_id = t.truck_id";
    $debugResult = $conn->query($debugSql);
    $totalRecords = $debugResult->fetch_assoc()['total_records'];
    error_log("Total maintenance records: " . $totalRecords);
    
    // Main query
    $sql = "SELECT 
            m.maintenance_id, 
            m.truck_id, 
            t.plate_no as licence_plate, 
            m.date_mtnce, 
            m.remarks, 
            m.status, 
            DATEDIFF(m.date_mtnce, CURDATE()) as days_remaining
            FROM maintenance_table m
            LEFT JOIN truck_table t ON m.truck_id = t.truck_id
            WHERE m.status != 'Completed' 
            AND t.is_deleted = 0
            AND (DATEDIFF(m.date_mtnce, CURDATE()) <= 7 OR m.date_mtnce < CURDATE())
            ORDER BY days_remaining ASC";
    
    error_log("Reminders query: " . $sql);
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("Reminders query error: " . $conn->error);
        return [];
    }
    
    error_log("Reminders found: " . $result->num_rows);
    
    $reminders = [];
    $updateStmt = $conn->prepare("UPDATE maintenance_table SET status = 'Overdue' WHERE maintenance_id = ?");
    $updateTruckStmt = $conn->prepare("UPDATE truck_table SET status = 'Overdue' WHERE truck_id = ?");
    
    while ($row = $result->fetch_assoc()) {
        
        if ($row['days_remaining'] < 0 && $row['status'] != 'Overdue') {
      
            $updateStmt->bind_param("i", $row['maintenance_id']);
            $updateStmt->execute();
            
        
            $updateTruckStmt->bind_param("i", $row['truck_id']);
            $updateTruckStmt->execute();
            
            
            $row['status'] = 'Overdue';
        }
        $reminders[] = $row;
    }
    
    $updateStmt->close();
    $updateTruckStmt->close();
    
    return $reminders;
}


function updateTruckStatusFromMaintenance($conn, $truckId, $status) {
    $newStatus = 'In Terminal'; 
    
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
     $rowsPerPage = isset($_GET['limit']) ? intval($_GET['limit']) : 5; // Add this line
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $showDeleted = isset($_GET['showDeleted']) ? filter_var($_GET['showDeleted'], FILTER_VALIDATE_BOOLEAN) : false;
    $data = getMaintenanceRecords($conn, $page, $rowsPerPage, $statusFilter, $showDeleted);
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

    case 'getAllRecordsForSearch':
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $showDeleted = isset($_GET['showDeleted']) ? filter_var($_GET['showDeleted'], FILTER_VALIDATE_BOOLEAN) : false;
    
$sql = "SELECT 
        m.maintenance_id,
        m.truck_id,
        m.maintenance_type_id,
        mt.type_name AS maintenance_type,
        t.plate_no AS licence_plate,
        m.date_mtnce,
        m.remarks,
        m.status,
        m.supplier_id,
        s.name AS supplier,
        m.cost,
        m.is_deleted,
        al.modified_by AS last_modified_by,
        al.modified_at AS last_modified_at,
        al.edit_reason AS edit_reasons,
        al.delete_reason
        FROM maintenance_table m
        LEFT JOIN truck_table t ON m.truck_id = t.truck_id
        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
        LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
        LEFT JOIN (
            SELECT maintenance_id, modified_by, modified_at, edit_reason, delete_reason,
                   ROW_NUMBER() OVER (PARTITION BY maintenance_id ORDER BY modified_at DESC) as rn
            FROM audit_logs_maintenance
        ) al ON m.maintenance_id = al.maintenance_id AND al.rn = 1";

    
    $params = [];
    $types = '';
    $whereClauses = [];
    
    if ($statusFilter !== 'all') {
        $whereClauses[] = "m.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    
    if ($showDeleted) {
        $whereClauses[] = "m.is_deleted = 1";
    } else {
        $whereClauses[] = "m.is_deleted = 0";
    }
    
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    $sql .= " ORDER BY m.maintenance_id DESC";
    
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
    
    echo json_encode(["records" => $records]);
    break;

        
  case 'add':
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->truckId, $data->date, $data->remarks, $data->status, $data->maintenanceTypeId, $data->supplierId)) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    $username = $_SESSION['username'] ?? 'System'; 
    $cost = isset($data->cost) ? floatval($data->cost) : 0;
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO maintenance_table (truck_id, maintenance_type_id, supplier_id, date_mtnce, remarks, status, cost) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssd", 
            $data->truckId,
            $data->maintenanceTypeId,
            $data->supplierId,
            $data->date, 
            $data->remarks,
            $data->status,
            $cost
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert maintenance record: " . $stmt->error);
        }
        
        $maintenanceId = $conn->insert_id;

        $auditStmt = $conn->prepare("INSERT INTO audit_logs_maintenance (maintenance_id, modified_by, modified_at, edit_reason, is_deleted) 
                                   VALUES (?, ?, NOW(), ?, 0)");
        $editReason = "Record created";
        $auditStmt->bind_param("iss", $maintenanceId, $username, $editReason);
        
        if (!$auditStmt->execute()) {
            throw new Exception("Failed to create audit log: " . $auditStmt->error);
        }
        
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Maintenance record created successfully"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Maintenance add error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    break;

    case 'fullDelete':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM maintenance_table WHERE maintenance_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }
    break;

    
case 'edit':
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->maintenanceId, $data->truckId, $data->maintenanceTypeId, $data->supplierId)) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    $username = $_SESSION['username'] ?? 'System'; 
    $cost = isset($data->cost) ? floatval($data->cost) : 0;
    $editReasons = isset($data->editReasons) && is_array($data->editReasons) ? implode(", ", $data->editReasons) : "";
    
    $conn->begin_transaction();
    
    try {
        // Update maintenance_table
        $stmt = $conn->prepare("UPDATE maintenance_table SET 
                               truck_id = ?, maintenance_type_id = ?, supplier_id = ?, 
                               date_mtnce = ?, remarks = ?, status = ?, cost = ?
                               WHERE maintenance_id = ?");
        $stmt->bind_param("iiisssdi", 
            $data->truckId,
            $data->maintenanceTypeId,
            $data->supplierId,
            $data->date, 
            $data->remarks,
            $data->status,
            $cost,
            $data->maintenanceId
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update maintenance record: " . $stmt->error);
        }
        
        // Insert audit log entry
        $auditStmt = $conn->prepare("INSERT INTO audit_logs_maintenance (maintenance_id, modified_by, modified_at, edit_reason, is_deleted) 
                                   VALUES (?, ?, NOW(), ?, 0)");
        $auditStmt->bind_param("iss", $data->maintenanceId, $username, $editReasons);
        
        if (!$auditStmt->execute()) {
            throw new Exception("Failed to create audit log: " . $auditStmt->error);
        }
        
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Maintenance record updated successfully"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Maintenance edit error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    break;
    
case 'delete':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $deleteReason = isset($_GET['reason']) ? $_GET['reason'] : '';

    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    $username = $_SESSION['username'] ?? 'System';

    $conn->begin_transaction();

    try {
        $auditStmt = $conn->prepare("INSERT INTO audit_logs_maintenance 
            (maintenance_id, modified_by, modified_at, delete_reason, is_deleted) 
            VALUES (?, ?, NOW(), ?, 1)");
        $auditStmt->bind_param("iss", $id, $username, $deleteReason);

        if (!$auditStmt->execute()) {
            throw new Exception("Failed to create delete audit log: " . $auditStmt->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Maintenance record deleted successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Maintenance delete error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    break;


case 'restore':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    // First get the maintenance record to restore
    $getMaintenance = $conn->prepare("SELECT truck_id, status FROM maintenance_table WHERE maintenance_id = ?");
    $getMaintenance->bind_param("i", $id);
    $getMaintenance->execute();
    $maintenance = $getMaintenance->get_result()->fetch_assoc();
    
    if (!$maintenance) {
        echo json_encode(["success" => false, "message" => "Maintenance record not found"]);
        exit;
    }

    // Restore the maintenance record
    $stmt = $conn->prepare("UPDATE audit_logs_maintenance
                        SET is_deleted = 0, delete_reason = NULL 
                        WHERE maintenance_id = ?");
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

    case 'getCounts':
    $counts = getMaintenanceCounts($conn);
    echo json_encode($counts);
    break;

    case 'checkMaintenance':
    $plateNo = isset($_GET['plateNo']) ? $_GET['plateNo'] : '';
    $tripDate = isset($_GET['tripDate']) ? $_GET['tripDate'] : '';
    
    if (empty($plateNo) || empty($tripDate)) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        break;
    }
    
    // Check for maintenance within 7 days before or after the trip date
$stmt = $conn->prepare("SELECT 
                        m.date_mtnce, 
                        m.remarks, 
                        mt.type_name AS maintenance_type,
                        t.plate_no AS licence_plate
                       FROM maintenance_table m
                       LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
                       LEFT JOIN truck_table t ON m.truck_id = t.truck_id
                       WHERE t.plate_no = ? 
                       AND m.is_deleted = 0
                       AND m.status != 'Completed'
                       AND DATEDIFF(m.date_mtnce, ?) BETWEEN -7 AND 7");
    $stmt->bind_param("ss", $plateNo, $tripDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $maintenance = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'hasConflict' => true,
            'maintenanceDate' => $maintenance['date_mtnce'],
            'maintenanceType' => $maintenance['maintenance_type'],
            'remarks' => $maintenance['remarks']
        ]);
    } else {
        echo json_encode(['success' => true, 'hasConflict' => false]);
    }
    break;
    
case 'getMaintenanceTypes':
    $sql = "SELECT maintenance_type_id, type_name FROM maintenance_types ORDER BY type_name";
    $result = $conn->query($sql);
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row;
    }
    echo json_encode(["success" => true, "types" => $types]);
    break;

case 'getSuppliers':
    $sql = "SELECT supplier_id, name FROM suppliers ORDER BY name";
    $result = $conn->query($sql);
    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
    echo json_encode(["success" => true, "suppliers" => $suppliers]);
    break;
        
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}

$conn->close();
?>