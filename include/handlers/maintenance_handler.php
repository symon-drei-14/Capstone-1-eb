<?php
header("Content-Type: application/json");
session_start();
date_default_timezone_set('Asia/Manila');
require 'dbhandler.php';

require_once 'NotificationService.php';
$notificationService = new NotificationService($conn);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function updateTruckStatusFromMaintenance($conn, $truckId) {
    $plateQuery = $conn->prepare("SELECT plate_no FROM truck_table WHERE truck_id = ?");
    if (!$plateQuery) {
        error_log("Failed to prepare plateQuery: " . $conn->error);
        return;
    }
    
    $plateQuery->bind_param("i", $truckId);
    $plateQuery->execute();
    $plateResult = $plateQuery->get_result();
    
    if ($plateResult->num_rows === 0) {
        return;
    }

    $tripStatus = null;
    
    $tripQuery = $conn->prepare("
        SELECT status 
        FROM trips 
        WHERE truck_id = ? 
        ORDER BY trip_date DESC 
        LIMIT 1
    ");
    
    if (!$tripQuery) {
        error_log("Failed to prepare tripQuery: " . $conn->error);
        return;
    }
    
    
    $tripQuery->bind_param("i", $truckId);
    $tripQuery->execute();
    $tripResult = $tripQuery->get_result();
    if ($tripResult->num_rows > 0) {
        $tripStatus = $tripResult->fetch_assoc()['status'];
    }
   

    $maintenanceStatus = null;
    $maintenanceQuery = $conn->prepare("
        SELECT m.status 
        FROM maintenance_table m
        WHERE m.truck_id = ? 
        AND m.status IN ('In Progress', 'Overdue', 'Pending', 'Completed')
        AND NOT EXISTS (
            SELECT 1 FROM audit_logs_maintenance al 
            WHERE al.maintenance_id = m.maintenance_id 
            AND al.is_deleted = 1
            AND al.modified_at = (
                SELECT MAX(al2.modified_at)
                FROM audit_logs_maintenance al2
                WHERE al2.maintenance_id = m.maintenance_id
            )
        )
        ORDER BY 
            CASE m.status 
                WHEN 'In Progress' THEN 1 
                WHEN 'Overdue' THEN 2 
                WHEN 'Pending' THEN 3 
                WHEN 'Completed' THEN 4
            END,
            m.date_mtnce DESC 
        LIMIT 1
    ");
    
    if (!$maintenanceQuery) {
        error_log("Failed to prepare maintenanceQuery: " . $conn->error);
        return;
    }
    
    $maintenanceQuery->bind_param("i", $truckId);
    $maintenanceQuery->execute();
    $maintenanceResult = $maintenanceQuery->get_result();
    if ($maintenanceResult->num_rows > 0) {
        $maintenanceStatus = $maintenanceResult->fetch_assoc()['status'];
    }

    $newStatus = 'In Terminal';

    if ($tripStatus === 'Enroute') {
        $newStatus = 'Enroute';
    } elseif ($maintenanceStatus === 'In Progress') {
        $newStatus = 'In Repair';
    } elseif ($maintenanceStatus === 'Overdue') {
        $newStatus = 'Overdue';
    } elseif ($maintenanceStatus === 'Pending') {
        $newStatus = 'In Terminal';
    } elseif ($maintenanceStatus === 'Completed' || $maintenanceStatus === null) {
        $newStatus = 'In Terminal';
    }

    $currentTime = date('Y-m-d H:i:s');
    $updateStmt = $conn->prepare("
        UPDATE truck_table 
        SET status = ?, last_modified_by = 'System', last_modified_at = ? 
        WHERE truck_id = ?
    ");
    
    if ($updateStmt) {
        $updateStmt->bind_param("ssi", $newStatus, $currentTime, $truckId);
        $updateStmt->execute();
    }
}

function getMaintenanceRecords($conn, $page = 1, $rowsPerPage = 5, $statusFilter = 'all', $showDeleted = false, $startDate = null, $endDate = null, $sortBy = 'maintenance_id', $sortDir = 'DESC') {
    // First, update any pending records that are now overdue
    $currentDate = date('Y-m-d');
    $updateOverdue = $conn->prepare("UPDATE maintenance_table m SET status = 'Overdue' 
                                    WHERE status = 'Pending'
                                AND date_mtnce < ?
                                AND NOT EXISTS (
                                    SELECT 1 FROM audit_logs_maintenance al 
                                    WHERE al.maintenance_id = m.maintenance_id 
                                    AND al.is_deleted = 1
                                    AND al.modified_at = (
                                        SELECT MAX(al2.modified_at)
                                        FROM audit_logs_maintenance al2
                                        WHERE al2.maintenance_id = m.maintenance_id
                                    )
                                )");

    if (!$updateOverdue) {
        error_log("Failed to prepare updateOverdue query: " . $conn->error);
        return [
            "records" => [], "totalPages" => 1, "currentPage" => 1, "totalRecords" => 0, "error" => "SQL preparation error"
        ];
    }
    $updateOverdue->bind_param("s", $currentDate);
    $updateOverdue->execute();

    // Update truck statuses for any newly overdue maintenance
    $getOverdueTrucks = $conn->query("SELECT DISTINCT m.truck_id, t.plate_no 
                                    FROM maintenance_table m
                                    JOIN truck_table t ON m.truck_id = t.truck_id
                                    WHERE m.status = 'Overdue' 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM audit_logs_maintenance al 
                                        WHERE al.maintenance_id = m.maintenance_id AND al.is_deleted = 1
                                        AND al.modified_at = (SELECT MAX(al2.modified_at) FROM audit_logs_maintenance al2 WHERE al2.maintenance_id = m.maintenance_id)
                                    ) AND t.is_deleted = 0");
    if ($getOverdueTrucks) {
        while ($row = $getOverdueTrucks->fetch_assoc()) {
            $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Overdue' WHERE truck_id = ?");
            if ($updateTruck) {
                $updateTruck->bind_param("i", $row['truck_id']);
                $updateTruck->execute();
            }
        }
    }

    $offset = ($page - 1) * $rowsPerPage;

    $allowedSortColumns = [
        'truck_id' => 'm.truck_id',
        'date_mtnce' => 'm.date_mtnce',
        'maintenance_id' => 'm.maintenance_id'
    ];

    $sortColumn = $allowedSortColumns[$sortBy] ?? 'm.maintenance_id';
    $sortDirection = (strtoupper($sortDir) === 'ASC') ? 'ASC' : 'DESC';

    $sql = "SELECT m.maintenance_id AS maintenanceId, m.truck_id AS truckId, t.plate_no AS licensePlate, m.date_mtnce AS maintenanceDate, m.remarks, m.status, s.name AS supplierName, m.supplier_id AS supplierId, mt.type_name AS maintenanceTypeName, m.maintenance_type_id AS maintenanceTypeId, m.cost, latest_audit.is_deleted AS isDeleted, latest_audit.delete_reason AS deleteReason, latest_audit.modified_by AS lastUpdatedBy, latest_audit.modified_at AS lastUpdatedAt, latest_audit.edit_reason AS editReason
        FROM maintenance_table m
        LEFT JOIN truck_table t ON m.truck_id = t.truck_id
        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
        LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
        LEFT JOIN (
            SELECT al1.maintenance_id, al1.modified_by, al1.modified_at, al1.edit_reason, al1.is_deleted, al1.delete_reason
            FROM audit_logs_maintenance al1
            WHERE al1.modified_at = (
                SELECT MAX(al2.modified_at)
                FROM audit_logs_maintenance al2
                WHERE al2.maintenance_id = al1.maintenance_id
            )
        ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id";

    $params = [];
    $types = '';
    $whereClauses = [];

    if ($statusFilter !== 'all') {
        $whereClauses[] = "m.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    if ($startDate) {
        $whereClauses[] = "m.date_mtnce >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if ($endDate) {
        $whereClauses[] = "m.date_mtnce <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if ($showDeleted) {
        $whereClauses[] = "latest_audit.is_deleted = 1";
    } else {
        $whereClauses[] = "(latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " ORDER BY $sortColumn $sortDirection, m.maintenance_id DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $rowsPerPage;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare main query: " . $conn->error);
        return ["records" => [], "totalPages" => 1, "currentPage" => 1, "totalRecords" => 0, "error" => "SQL preparation error: " . $conn->error];
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    $countSql = "SELECT COUNT(*) as total FROM maintenance_table m
                LEFT JOIN (
                    SELECT al1.maintenance_id, al1.is_deleted
                    FROM audit_logs_maintenance al1
                    WHERE al1.modified_at = (SELECT MAX(al2.modified_at) FROM audit_logs_maintenance al2 WHERE al2.maintenance_id = al1.maintenance_id)
                ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id";

    $countParams = [];
    $countTypes = '';
    $countWhereClauses = [];

    if ($statusFilter !== 'all') {
        $countWhereClauses[] = "m.status = ?";
        $countParams[] = $statusFilter;
        $countTypes .= "s";
    }
    if ($startDate) {
        $countWhereClauses[] = "m.date_mtnce >= ?";
        $countParams[] = $startDate;
        $countTypes .= "s";
    }
    if ($endDate) {
        $countWhereClauses[] = "m.date_mtnce <= ?";
        $countParams[] = $endDate;
        $countTypes .= "s";
    }
    if ($showDeleted) {
        $countWhereClauses[] = "latest_audit.is_deleted = 1";
    } else {
        $countWhereClauses[] = "(latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)";
    }

    if (!empty($countWhereClauses)) {
        $countSql .= " WHERE " . implode(" AND ", $countWhereClauses);
    }

    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        error_log("Failed to prepare count query: " . $conn->error);
        return ["records" => $records, "totalPages" => 1, "currentPage" => $page, "totalRecords" => count($records), "error" => "Count query error: " . $conn->error];
    }
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRows = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);

    return ["records" => $records, "totalPages" => $totalPages, "currentPage" => $page, "totalRecords" => $totalRows];
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
    )";
    
    $result = $conn->query($sql);
    if (!$result) {
        error_log("SQL Error in getMaintenanceCounts: " . $conn->error);
        return [
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'completed_this_month' => 0
        ];
    }
    return $result->fetch_assoc();
}


function getMaintenanceHistory($conn, $truckId) {
 
    $sql = "SELECT m.maintenance_id, m.date_mtnce, m.remarks, m.status, m.cost,
                   mt.type_name as maintenance_type_name, s.name as supplier_name,
                   latest_audit.modified_by as last_modified_by, latest_audit.modified_at as last_modified_at,
                   t.plate_no as licence_plate
            FROM maintenance_table m
            LEFT JOIN truck_table t ON m.truck_id = t.truck_id
            LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
            LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
            LEFT JOIN (
                
                SELECT
                    al1.maintenance_id,
                    al1.modified_by,
                    al1.modified_at,
                    al1.is_deleted
                FROM audit_logs_maintenance al1
                WHERE al1.modified_at = (
                    SELECT MAX(al2.modified_at)
                    FROM audit_logs_maintenance al2
                    WHERE al2.maintenance_id = al1.maintenance_id
                )
            ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id
            WHERE m.truck_id = ?
            AND t.is_deleted = 0
            
            AND (latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)
            ORDER BY m.date_mtnce DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare history query: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $truckId);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    return $history;
}

function updateTotalMaintenanceCost($conn, $maintenanceId) {
    $sumStmt = $conn->prepare("SELECT SUM(amount) as total FROM maintenance_expenses WHERE maintenance_id = ?");
    $sumStmt->bind_param("i", $maintenanceId);
    $sumStmt->execute();
    $result = $sumStmt->get_result();
    $row = $result->fetch_assoc();
    $totalCost = $row['total'] ? (float)$row['total'] : 0.00;

    $updateStmt = $conn->prepare("UPDATE maintenance_table SET cost = ? WHERE maintenance_id = ?");
    $updateStmt->bind_param("di", $totalCost, $maintenanceId);
    $updateStmt->execute();
}

function getMaintenanceReminders($conn) {
    $currentDate = date('Y-m-d');
    $sql = "SELECT
                m.maintenance_id,
                m.truck_id,
                t.plate_no AS licence_plate,
                m.date_mtnce,
                m.remarks,
                m.status,
                DATEDIFF(m.date_mtnce, ?) AS days_remaining
            FROM maintenance_table m
            JOIN truck_table t ON m.truck_id = t.truck_id
            WHERE
                m.status != 'Completed'
                AND t.is_deleted = 0
                AND (DATEDIFF(m.date_mtnce, ?) <= 7 OR m.date_mtnce < ?)
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
            ORDER BY days_remaining ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Reminders query error: " . $conn->error);
        return [];
    }
    $stmt->bind_param("sss", $currentDate, $currentDate, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reminders = [];
    $updateStmt = $conn->prepare("UPDATE maintenance_table SET status = 'Overdue' WHERE maintenance_id = ?");
    $updateTruckStmt = $conn->prepare("UPDATE truck_table SET status = 'Overdue' WHERE truck_id = ?");
    
    while ($row = $result->fetch_assoc()) {
        if ($row['days_remaining'] < 0 && $row['status'] != 'Overdue') {
            if ($updateStmt) {
                $updateStmt->bind_param("i", $row['maintenance_id']);
                $updateStmt->execute();
            }
            
            if ($updateTruckStmt) {
                $updateTruckStmt->bind_param("i", $row['truck_id']);
                $updateTruckStmt->execute();
            }
            
            $row['status'] = 'Overdue';
        }
        $reminders[] = $row;
    }
    
    if ($updateStmt) $updateStmt->close();
    if ($updateTruckStmt) $updateTruckStmt->close();
    
    return $reminders;
}

// Process the request based on action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
       case 'getRecords':
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rowsPerPage = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $showDeleted = isset($_GET['showDeleted']) ? filter_var($_GET['showDeleted'], FILTER_VALIDATE_BOOLEAN) : false;
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

    // Read the new sorting parameters from the URL
    $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'maintenance_id';
    $sortDir = isset($_GET['sortDir']) ? $_GET['sortDir'] : 'DESC';

    $data = getMaintenanceRecords($conn, $page, $rowsPerPage, $statusFilter, $showDeleted, $startDate, $endDate, $sortBy, $sortDir);
    echo json_encode($data);
    break;

        case 'getHistory':
            $truckId = isset($_GET['truckId']) ? intval($_GET['truckId']) : 0;
            $history = getMaintenanceHistory($conn, $truckId);
            echo json_encode(["history" => $history]);
            break;
            
        case 'getReminders':
            $reminders = getMaintenanceReminders($conn);
            echo json_encode(["success" => true, "reminders" => $reminders]); 
            break;

       case 'getAllRecordsForSearch':
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
        $showDeleted = isset($_GET['showDeleted']) ? filter_var($_GET['showDeleted'], FILTER_VALIDATE_BOOLEAN) : false;
        $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
        $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
        
        $sql = "SELECT 
            m.maintenance_id AS maintenanceId,
            m.truck_id AS truckId,
            m.maintenance_type_id AS maintenanceTypeId,
            mt.type_name AS maintenanceTypeName,
            t.plate_no AS licensePlate,
            m.date_mtnce AS maintenanceDate,
            m.remarks,
            m.status,
            m.supplier_id AS supplierId,
            s.name AS supplierName,
            m.cost,
            al.is_deleted AS isDeleted,
            al.modified_by AS lastUpdatedBy,
            al.modified_at AS lastUpdatedAt,
            al.edit_reason AS editReason,
            al.delete_reason AS deleteReason
        FROM maintenance_table m
        LEFT JOIN truck_table t ON m.truck_id = t.truck_id
        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
        LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
        LEFT JOIN (
            SELECT maintenance_id, modified_by, modified_at, edit_reason, delete_reason, is_deleted,
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
        
        // Add date range filtering
        if ($startDate) {
            $whereClauses[] = "m.date_mtnce >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        
        if ($endDate) {
            $whereClauses[] = "m.date_mtnce <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        
        if ($showDeleted) {
            $whereClauses[] = "COALESCE(al.is_deleted, 0) = 1";
        } else {
            $whereClauses[] = "COALESCE(al.is_deleted, 0) = 0";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $sql .= " ORDER BY m.maintenance_id DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Failed to prepare getAllRecordsForSearch query: " . $conn->error);
            echo json_encode(["success" => false, "message" => "Database error", "records" => []]);
            break;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        
        echo json_encode(["success" => true, "records" => $records]);
        break;
            
case 'add':
            $data = json_decode(file_get_contents("php://input"));

            if (!isset($data->truckId, $data->date, $data->remarks, $data->status, $data->maintenanceTypeId)) {
                echo json_encode(["success" => false, "message" => "Missing required fields"]);
                exit;
            }

            $username = $_SESSION['username'] ?? 'System'; 
            $cost = isset($data->cost) ? floatval($data->cost) : 0;

            $supplierId = (!empty($data->supplierId) && $data->supplierId !== 0) ? $data->supplierId : null;

            $conn->begin_transaction();
            
            try {
                $stmt = $conn->prepare("INSERT INTO maintenance_table (truck_id, maintenance_type_id, supplier_id, date_mtnce, remarks, status, cost) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("iiisssd", 
                    $data->truckId,
                    $data->maintenanceTypeId,
                    $supplierId,
                    $data->date, 
                    $data->remarks,
                    $data->status,
                    $cost
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert maintenance record: " . $stmt->error);
                }
                
                $maintenanceId = $conn->insert_id;

                $currentTime = date('Y-m-d H:i:s');
                $auditStmt = $conn->prepare("INSERT INTO audit_logs_maintenance (maintenance_id, modified_by, modified_at, edit_reason, is_deleted) 
                                             VALUES (?, ?, ?, ?, 0)");
                
                if (!$auditStmt) {
                    throw new Exception("Failed to prepare audit statement: " . $conn->error);
                }
                
                $editReason = "Record created";
                $auditStmt->bind_param("isss", $maintenanceId, $username, $currentTime, $editReason);
                
                if (!$auditStmt->execute()) {
                    throw new Exception("Failed to create audit log: " . $auditStmt->error);
                }

                updateTruckStatusFromMaintenance($conn, $data->truckId);


                $driverQuery = $conn->prepare("SELECT driver_id FROM drivers_table WHERE assigned_truck_id = ?");
                $driverQuery->bind_param("i", $data->truckId);
                $driverQuery->execute();
                $driverRes = $driverQuery->get_result();

                if ($driverRes->num_rows > 0) {
                    $driverData = $driverRes->fetch_assoc();
                    $targetDriverId = $driverData['driver_id'];

                    $typeQuery = $conn->prepare("SELECT type_name FROM maintenance_types WHERE maintenance_type_id = ?");
                    $typeQuery->bind_param("i", $data->maintenanceTypeId);
                    $typeQuery->execute();
                    $typeRes = $typeQuery->get_result();
                    $typeName = ($typeRes->num_rows > 0) ? $typeRes->fetch_assoc()['type_name'] : 'Maintenance';

                    $formattedDate = date('M j, Y', strtotime($data->date));

                    $notificationData = [
                        'title' => 'Maintenance Alert',
                        'body' => "Your truck is scheduled for $typeName on $formattedDate.",
                        'maintenance_id' => $maintenanceId,
                        'truck_id' => $data->truckId
                    ];

                    if (method_exists($notificationService, 'sendMaintenanceNotification')) {
                        $notificationService->sendMaintenanceNotification($targetDriverId, $notificationData);
                    } else {
                        $payload = [
                            'type' => 'maintenance',
                            'maintenance_id' => (string)$maintenanceId,
                            'click_action' => 'MAINTENANCE_SCREEN'
                        ];
                        $notificationService->createNotification(
                            $targetDriverId, 
                            $notificationData['title'], 
                            $notificationData['body'], 
                            'maintenance', 
                            null, 
                            $payload
                        );
                    }
                    error_log("Maintenance notification sent to driver ID: " . $targetDriverId);
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

    // Start transaction for data integrity
    $conn->begin_transaction();
    
    try {
       $stmt_expenses = $conn->prepare("DELETE FROM maintenance_expenses WHERE maintenance_id = ?");
        if (!$stmt_expenses) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt_expenses->bind_param("i", $id);
        $stmt_expenses->execute();
        $stmt_expenses->close();

        $stmt1 = $conn->prepare("DELETE FROM audit_logs_maintenance WHERE maintenance_id = ?");
        if (!$stmt1) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Then delete the main maintenance record
        $stmt2 = $conn->prepare("DELETE FROM maintenance_table WHERE maintenance_id = ?");
        if (!$stmt2) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();
        
        // Commit the transaction
        $conn->commit();
        echo json_encode(["success" => true]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    break;
            
case 'edit':
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->maintenanceId, $data->truckId, $data->maintenanceTypeId)) {
                echo json_encode(["success" => false, "message" => "Missing required fields"]);
                exit;
            }

            $username = $_SESSION['username'] ?? 'System'; 
            $cost = isset($data->cost) ? floatval($data->cost) : 0;
            $editReasons = $data->editReasons ?? null;

            $supplierId = (!empty($data->supplierId) && $data->supplierId !== 0) ? $data->supplierId : null;

            $conn->begin_transaction();
            
            try {
                $stmt = $conn->prepare("UPDATE maintenance_table SET 
                                        truck_id = ?, maintenance_type_id = ?, supplier_id = ?, 
                                        date_mtnce = ?, remarks = ?, status = ?, cost = ?
                                        WHERE maintenance_id = ?");
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare update statement: " . $conn->error);
                }
                
                $stmt->bind_param("iiisssdi", 
                    $data->truckId,
                    $data->maintenanceTypeId,
                    $supplierId,
                    $data->date, 
                    $data->remarks,
                    $data->status,
                    $cost,
                    $data->maintenanceId
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update maintenance record: " . $stmt->error);
                }

                $currentTime = date('Y-m-d H:i:s');
                $auditStmt = $conn->prepare("INSERT INTO audit_logs_maintenance (maintenance_id, modified_by, modified_at, edit_reason, is_deleted) 
                                             VALUES (?, ?, ?, ?, 0)");
                
                if (!$auditStmt) {
                    throw new Exception("Failed to prepare audit statement: " . $conn->error);
                }
                
                $auditStmt->bind_param("isss", $data->maintenanceId, $username, $currentTime, $editReasons);
                
                if (!$auditStmt->execute()) {
                    throw new Exception("Failed to create audit log: " . $auditStmt->error);
                }

                updateTruckStatusFromMaintenance($conn, $data->truckId);

                $conn->commit();

                if ($data->status === 'In Progress' || $data->status === 'Completed') {

                    $driverQuery = $conn->prepare("SELECT driver_id FROM drivers_table WHERE assigned_truck_id = ?");
                    $driverQuery->bind_param("i", $data->truckId);
                    $driverQuery->execute();
                    $driverRes = $driverQuery->get_result();

                    if ($driverRes->num_rows > 0) {
                        $driverData = $driverRes->fetch_assoc();
                        $targetDriverId = $driverData['driver_id'];

                        $notifData = [
                            'maintenance_id' => $data->maintenanceId,
                            'status' => $data->status,
                            'truck_id' => $data->truckId
                        ];

                        if (method_exists($notificationService, 'sendMaintenanceUpdateNotification')) {
                            $notificationService->sendMaintenanceUpdateNotification($targetDriverId, $notifData);
                            error_log("Maintenance status update sent to driver ID: $targetDriverId (Status: {$data->status})");
                        } else {
                             $title = "Maintenance Update";
                             $body = "Your truck maintenance status is now: " . $data->status;
                             $notificationService->createNotification($targetDriverId, $title, $body, 'maintenance_update', null, $notifData);
                        }
                    }
                }

                echo json_encode(["success" => true, "message" => "Maintenance record updated successfully"]);
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Maintenance edit error: " . $e->getMessage());
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
            break;

             case 'getExpenses':
        $maintenanceId = isset($_GET['maintenanceId']) ? intval($_GET['maintenanceId']) : 0;
        if ($maintenanceId <= 0) {
            echo json_encode(["success" => false, "message" => "Invalid Maintenance ID"]);
            break;
        }
        $stmt = $conn->prepare("SELECT expense_id, expense_type, amount, receipt_image, submitted_at FROM maintenance_expenses WHERE maintenance_id = ? ORDER BY submitted_at DESC");
        $stmt->bind_param("i", $maintenanceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $expenses = [];
        while($row = $result->fetch_assoc()) {
            $expenses[] = $row;
        }
        echo json_encode(["success" => true, "expenses" => $expenses]);
        break;

    case 'addExpense':
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->maintenanceId, $data->expenseType, $data->amount)) {
            echo json_encode(["success" => false, "message" => "Missing required expense data"]);
            break;
        }
        $receiptImage = $data->receiptImage ?? null;
        $conn->begin_transaction();
        try {
            $currentTime = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO maintenance_expenses (maintenance_id, expense_type, amount, receipt_image, submitted_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $data->maintenanceId, $data->expenseType, $data->amount, $receiptImage, $currentTime);
            $stmt->execute();
            updateTotalMaintenanceCost($conn, $data->maintenanceId);
            $conn->commit();
            echo json_encode(["success" => true, "message" => "Expense added."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Database error."]);
        }
        break;

    case 'updateExpense':
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->expenseId, $data->maintenanceId, $data->expenseType, $data->amount)) {
            echo json_encode(["success" => false, "message" => "Missing data for update"]);
            break;
        }
        $conn->begin_transaction();
        try {
            if ($data->receiptImage) {
                $stmt = $conn->prepare("UPDATE maintenance_expenses SET expense_type = ?, amount = ?, receipt_image = ? WHERE expense_id = ?");
                $stmt->bind_param("sdsi", $data->expenseType, $data->amount, $data->receiptImage, $data->expenseId);
            } else {
                $stmt = $conn->prepare("UPDATE maintenance_expenses SET expense_type = ?, amount = ? WHERE expense_id = ?");
                $stmt->bind_param("sdi", $data->expenseType, $data->amount, $data->expenseId);
            }
            $stmt->execute();
            updateTotalMaintenanceCost($conn, $data->maintenanceId);
            $conn->commit();
            echo json_encode(["success" => true, "message" => "Expense updated."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Database error."]);
        }
        break;

        case 'checkPreventiveEditDate':
    $truckId = isset($_GET['truckId']) ? intval($_GET['truckId']) : 0;
    $maintenanceId = isset($_GET['maintenanceId']) ? intval($_GET['maintenanceId']) : 0;
    
    if ($truckId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid truck ID"]);
        exit;
    }
    
    // Get the date of the maintenance record being edited
    $currentDateStmt = $conn->prepare("SELECT date_mtnce FROM maintenance_table WHERE maintenance_id = ?");
    $currentDateStmt->bind_param("i", $maintenanceId);
    $currentDateStmt->execute();
    $currentDateResult = $currentDateStmt->get_result();
    
    if ($currentDateResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Maintenance record not found"]);
        exit;
    }
    
    $currentDate = $currentDateResult->fetch_assoc()['date_mtnce'];
    
    // Check for existing preventive maintenance for this truck (excluding the current record being edited)
    $sql = "SELECT date_mtnce 
            FROM maintenance_table m
            LEFT JOIN (
                SELECT maintenance_id, is_deleted,
                       ROW_NUMBER() OVER (PARTITION BY maintenance_id ORDER BY modified_at DESC) as rn
                FROM audit_logs_maintenance
            ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id AND latest_audit.rn = 1
            WHERE m.truck_id = ? 
            AND m.maintenance_type_id = 1 
            AND m.maintenance_id != ?
            AND m.status IN ('Completed', 'Pending', 'In Progress')
            AND (latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)
            ORDER BY ABS(DATEDIFF(m.date_mtnce, ?))
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        break;
    }
    
    $stmt->bind_param("iis", $truckId, $maintenanceId, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingMaintenance = $result->fetch_assoc();
        $existingDate = new DateTime($existingMaintenance['date_mtnce']);
        $newDate = new DateTime($currentDate);
        
        // Calculate the difference in months
        $interval = $existingDate->diff($newDate);
        $monthsDiff = ($interval->y * 12) + $interval->m;
        
        if ($monthsDiff < 6) {
            $message = "This truck already has a preventive maintenance scheduled/completed on " . 
                      $existingDate->format('F j, Y') . ". Preventive maintenance must be at least 6 months apart.";
            
            echo json_encode([
                "success" => true,
                "hasConflict" => true,
                "message" => $message,
                "existingDate" => $existingMaintenance['date_mtnce']
            ]);
        } else {
            echo json_encode(["success" => true, "hasConflict" => false]);
        }
    } else {
        echo json_encode(["success" => true, "hasConflict" => false]);
    }
    break;

    case 'deleteExpense':
        $expenseId = isset($_GET['expenseId']) ? intval($_GET['expenseId']) : 0;
        if ($expenseId <= 0) {
            echo json_encode(["success" => false, "message" => "Invalid Expense ID"]);
            break;
        }
        $conn->begin_transaction();
        try {
            $idStmt = $conn->prepare("SELECT maintenance_id FROM maintenance_expenses WHERE expense_id = ?");
            $idStmt->bind_param("i", $expenseId);
            $idStmt->execute();
            $maintenanceId = $idStmt->get_result()->fetch_assoc()['maintenance_id'];

            if ($maintenanceId) {
                $deleteStmt = $conn->prepare("DELETE FROM maintenance_expenses WHERE expense_id = ?");
                $deleteStmt->bind_param("i", $expenseId);
                $deleteStmt->execute();
                updateTotalMaintenanceCost($conn, $maintenanceId);
                $conn->commit();
                echo json_encode(["success" => true, "message" => "Expense deleted."]);
            } else {
                throw new Exception("Maintenance ID not found for expense.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Database error."]);
        }
        break;

       case 'delete':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $deleteReason = isset($_GET['reason']) ? $_GET['reason'] : '';

            if ($id <= 0) {
                echo json_encode(["success" => false, "message" => "Invalid ID"]);
                exit;
            }

            $getTruckId = $conn->prepare("SELECT truck_id FROM maintenance_table WHERE maintenance_id = ?");
            
            if (!$getTruckId) {
                echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
                break;
            }
            
            $getTruckId->bind_param("i", $id);
            $getTruckId->execute();
            $truckResult = $getTruckId->get_result();
            $truckId = $truckResult->fetch_assoc()['truck_id'] ?? null;

            $username = $_SESSION['username'] ?? 'System';

            $conn->begin_transaction();

            try {
                $currentTime = date('Y-m-d H:i:s');
                $auditStmt = $conn->prepare("INSERT INTO audit_logs_maintenance 
                    (maintenance_id, modified_by, modified_at, delete_reason, is_deleted) 
                    VALUES (?, ?, ?, ?, 1)");
                
                if (!$auditStmt) {
                    throw new Exception("Failed to prepare audit statement: " . $conn->error);
                }
                
                $auditStmt->bind_param("isss", $id, $username, $currentTime, $deleteReason);

                if (!$auditStmt->execute()) {
                    throw new Exception("Failed to create delete audit log: " . $auditStmt->error);
                }

                if ($truckId) {
                    updateTruckStatusFromMaintenance($conn, $truckId);
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

    // Get the maintenance record details including maintenance type
    $getMaintenance = $conn->prepare("SELECT m.truck_id, m.status, m.maintenance_type_id, m.date_mtnce 
                                     FROM maintenance_table m 
                                     WHERE m.maintenance_id = ?");
    
    if (!$getMaintenance) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        break;
    }
    
    $getMaintenance->bind_param("i", $id);
    $getMaintenance->execute();
    $maintenance = $getMaintenance->get_result()->fetch_assoc();
    
    if (!$maintenance) {
        echo json_encode(["success" => false, "message" => "Maintenance record not found"]);
        exit;
    }

    // Check 6-month interval for preventive maintenance
    if ($maintenance['maintenance_type_id'] == 1) { // Preventive maintenance
        $checkDateStmt = $conn->prepare("
            SELECT m.date_mtnce, m.maintenance_id
            FROM maintenance_table m
            LEFT JOIN (
                SELECT maintenance_id, is_deleted,
                       ROW_NUMBER() OVER (PARTITION BY maintenance_id ORDER BY modified_at DESC) as rn
                FROM audit_logs_maintenance
            ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id AND latest_audit.rn = 1
            WHERE m.truck_id = ? 
            AND m.maintenance_type_id = 1 
            AND m.status IN ('Completed', 'Pending', 'In Progress', 'Overdue')
            AND m.maintenance_id != ?
            AND (latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)
        ");
        
        if ($checkDateStmt) {
            $checkDateStmt->bind_param("ii", $maintenance['truck_id'], $id);
            $checkDateStmt->execute();
            $dateResult = $checkDateStmt->get_result();
            
            $restoreDate = new DateTime($maintenance['date_mtnce']);
            
            while ($row = $dateResult->fetch_assoc()) {
                $existingDate = new DateTime($row['date_mtnce']);
                
                $interval = $restoreDate->diff($existingDate);
                $monthsDiff = ($interval->y * 12) + $interval->m;
                
                if ($monthsDiff < 6) {
                    $conflictDate = $existingDate->format('F j, Y');
                    $restoreDateFormatted = $restoreDate->format('F j, Y');
                    
                    echo json_encode([
                        "success" => false, 
                        "message" => "Cannot restore this preventive maintenance schedule. It violates the 6-month interval rule. There is already a preventive maintenance scheduled/completed on {$conflictDate}. Preventive maintenance must be at least 6 months apart from any existing preventive maintenance (restore date: {$restoreDateFormatted})."
                    ]);
                    exit;
                }
            }
        }
    }

    $conn->begin_transaction();
    
    try {
        $checkDeletedStmt = $conn->prepare("
            SELECT is_deleted 
            FROM audit_logs_maintenance 
            WHERE maintenance_id = ? 
            ORDER BY modified_at DESC 
            LIMIT 1
        ");
        $checkDeletedStmt->bind_param("i", $id);
        $checkDeletedStmt->execute();
        $deletedResult = $checkDeletedStmt->get_result();
        
        if ($deletedResult->num_rows > 0) {
            $latestLog = $deletedResult->fetch_assoc();
            
            if ($latestLog['is_deleted'] == 1) {
                $username = $_SESSION['username'] ?? 'System';
                $currentTime = date('Y-m-d H:i:s');
                
                $stmt = $conn->prepare("INSERT INTO audit_logs_maintenance 
                                      (maintenance_id, modified_by, modified_at, edit_reason, is_deleted) 
                                      VALUES (?, ?, ?, 'Record restored', 0)");
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare restore statement: " . $conn->error);
                }
                
                $stmt->bind_param("iss", $id, $username, $currentTime);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to restore maintenance record: " . $stmt->error);
                }

                updateTruckStatusFromMaintenance($conn, $maintenance['truck_id']);
                
                $conn->commit();
                echo json_encode(["success" => true, "message" => "Maintenance record restored successfully"]);
            } else {
                $conn->rollback();
                echo json_encode(["success" => false, "message" => "Record is not currently deleted"]);
            }
        } else {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "No audit history found for this record"]);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Restore error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
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
    
    // First get the truck ID from the plate number
    $truckStmt = $conn->prepare("SELECT truck_id FROM truck_table WHERE plate_no = ? AND is_deleted = 0");
    $truckStmt->bind_param("s", $plateNo);
    $truckStmt->execute();
    $truckResult = $truckStmt->get_result();
    
    if ($truckResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Truck not found']);
        break;
    }
    
    $truckId = $truckResult->fetch_assoc()['truck_id'];
    
    $stmt = $conn->prepare("
    SELECT 
        m.date_mtnce, 
        m.remarks, 
        mt.type_name AS maintenance_type
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
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        break;
    }
    
    $stmt->bind_param("is", $truckId, $tripDate);
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

           case 'checkPreventiveDate':
    $truckId = isset($_GET['truckId']) ? intval($_GET['truckId']) : 0;
    
    if ($truckId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid truck ID"]);
        exit;
    }
    
    // Check for any preventive maintenance (completed, pending, or in progress) in the last 6 months
    $sql = "SELECT MAX(date_mtnce) as last_date 
            FROM maintenance_table m
            LEFT JOIN (
                SELECT maintenance_id, is_deleted,
                       ROW_NUMBER() OVER (PARTITION BY maintenance_id ORDER BY modified_at DESC) as rn
                FROM audit_logs_maintenance
            ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id AND latest_audit.rn = 1
            WHERE m.truck_id = ? 
            AND m.maintenance_type_id = 1 
            AND m.status IN ('Completed', 'Pending', 'In Progress')
            AND (latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        break;
    }
    
    $stmt->bind_param("i", $truckId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(["success" => true, "lastDate" => $row['last_date']]);
    } else {
        echo json_encode(["success" => true, "lastDate" => null]);
    }
    break;

    case 'getMaintenanceFrequency':
    
    $sql = "SELECT 
                YEAR(m.date_mtnce) as maintenance_year, 
                t.plate_no,
                COUNT(m.maintenance_id) as maintenance_count
            FROM maintenance_table m
            JOIN truck_table t ON m.truck_id = t.truck_id
            LEFT JOIN (
              
                SELECT 
                    al1.maintenance_id,
                    al1.is_deleted
                FROM audit_logs_maintenance al1
                WHERE al1.modified_at = (
                    SELECT MAX(al2.modified_at)
                    FROM audit_logs_maintenance al2
                    WHERE al2.maintenance_id = al1.maintenance_id
                )
            ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id
            
            WHERE t.is_deleted = 0 AND (latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL) AND m.status = 'Completed'
            GROUP BY maintenance_year, t.plate_no
            ORDER BY maintenance_year ASC, t.plate_no ASC";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $conn->error]);
        exit;
    }

    $rawData = [];
    $years = [];
    $trucks = [];

    //gather all unique years and trucks from the data.
    while ($row = $result->fetch_assoc()) {
        $rawData[] = $row;
        if (!in_array($row['maintenance_year'], $years)) {
            $years[] = $row['maintenance_year'];
        }
        if (!in_array($row['plate_no'], $trucks)) {
            $trucks[] = $row['plate_no'];
        }
    }

    // sorts
    sort($years, SORT_NUMERIC);
    sort($trucks, SORT_STRING);

    
    $series = [];
    foreach ($trucks as $truckPlate) {
        $dataPoints = [];
        foreach ($years as $year) {
            $count = 0; 
            foreach ($rawData as $data) {
                if ($data['plate_no'] == $truckPlate && $data['maintenance_year'] == $year) {
                    $count = (int)$data['maintenance_count'];
                    break;
                }
            }
            $dataPoints[] = $count;
        }
        $series[] = [
            'name' => $truckPlate,
            'data' => $dataPoints
        ];
    }

    
    echo json_encode([
        'success' => true,
        'series' => $series,
        'categories' => $years
    ]);
    break;


    case 'getAssignedTruck':
        $driverId = isset($_GET['driverId']) ? $_GET['driverId'] : '';
        
        if (empty($driverId)) {
            echo json_encode(["success" => false, "message" => "Driver ID required"]);
            break;
        }

        $stmt = $conn->prepare("
            SELECT d.assigned_truck_id, t.plate_no 
            FROM drivers_table d 
            LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id 
            WHERE d.driver_id = ?
        ");
        
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "DB Error: " . $conn->error]);
            break;
        }

        $stmt->bind_param("s", $driverId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['assigned_truck_id']) {
                echo json_encode([
                    "success" => true, 
                    "truck_id" => $row['assigned_truck_id'], 
                    "plate_no" => $row['plate_no']
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "No truck assigned"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Driver not found"]);
        }
        break;

    case 'getAuditLogs':
            $maintenanceId = isset($_GET['maintenanceId']) ? intval($_GET['maintenanceId']) : 0;
            if ($maintenanceId <= 0) {
                echo json_encode(["success" => false, "message" => "Invalid ID"]);
                break;
            }

           
            $stmt = $conn->prepare("SELECT modified_by, modified_at, edit_reason, delete_reason, is_deleted 
                                    FROM audit_logs_maintenance 
                                    WHERE maintenance_id = ? 
                                    ORDER BY modified_at DESC");
            $stmt->bind_param("i", $maintenanceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            
            echo json_encode(["success" => true, "logs" => $logs]);
            break;
            
        case 'getMaintenanceTypes':
            $sql = "SELECT maintenance_type_id, type_name FROM maintenance_types ORDER BY type_name";
            $result = $conn->query($sql);
            
            if (!$result) {
                echo json_encode(["success" => false, "message" => "Database error: " . $conn->error, "types" => []]);
                break;
            }
            
            $types = [];
            while ($row = $result->fetch_assoc()) {
                $types[] = $row;
            }
            echo json_encode(["success" => true, "types" => $types]);
            break;

        case 'getSuppliers':
            $sql = "SELECT supplier_id, name FROM suppliers ORDER BY name";
            $result = $conn->query($sql);
            
            if (!$result) {
                echo json_encode(["success" => false, "message" => "Database error: " . $conn->error, "suppliers" => []]);
                break;
            }
            
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
    
} catch (Exception $e) {
    error_log("Maintenance handler error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An unexpected error occurred: " . $e->getMessage()]);
} catch (Error $e) {
    error_log("Maintenance handler fatal error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "A system error occurred. Please contact support."]);
}

$conn->close();
?>