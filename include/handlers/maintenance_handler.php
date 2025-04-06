<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Get all maintenance records with pagination
function getMaintenanceRecords($conn, $page = 1, $rowsPerPage = 5) {
    $offset = ($page - 1) * $rowsPerPage;
    
    // Modified query to not depend on trucks table
    $sql = "SELECT maintenance_id, truck_id, licence_plate, date_mtnce, 
            remarks, status, supplier, cost 
            FROM maintenance
            ORDER BY maintenance_id DESC
            LIMIT $offset, $rowsPerPage";
    
    $result = $conn->query($sql);
    
    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    } else {
        // Log error for debugging
        error_log("Query error: " . $conn->error);
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM maintenance";
    $countResult = $conn->query($countSql);
    $totalRows = $countResult ? $countResult->fetch_assoc()['total'] : 0;
    $totalPages = ceil($totalRows / $rowsPerPage);
    
    return [
        "records" => $records,
        "totalPages" => $totalPages,
        "currentPage" => $page
    ];
}

// Get maintenance history for a specific truck
function getMaintenanceHistory($conn, $truckId) {
    $sql = "SELECT maintenance_id, date_mtnce, remarks, status, supplier, cost 
            FROM maintenance
            WHERE truck_id = ?
            ORDER BY date_mtnce DESC";
    
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
    // Modified query to not depend on trucks table
    $sql = "SELECT maintenance_id, truck_id, licence_plate, date_mtnce, 
            remarks, status, supplier, cost, 
            DATEDIFF(date_mtnce, CURDATE()) as days_remaining
            FROM maintenance
            WHERE status != 'Completed' 
            AND (DATEDIFF(date_mtnce, CURDATE()) <= 7)
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

// Process the request based on action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getRecords':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $data = getMaintenanceRecords($conn, $page);
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
        
        if (!isset($data->truckId, $data->date, $data->remarks, $data->status)) {
            echo json_encode(["success" => false, "message" => "Incomplete data"]);
            exit;
        }
        
        $licensePlate = isset($data->licensePlate) ? $data->licensePlate : '';
        $supplier = isset($data->supplier) ? $data->supplier : '';
        $cost = isset($data->cost) ? $data->cost : 0;
        
        $stmt = $conn->prepare("INSERT INTO maintenance (truck_id, licence_plate, date_mtnce, remarks, status, supplier, cost) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssd", 
            $data->truckId,
            $licensePlate,
            $data->date, 
            $data->remarks,
            $data->status,
            $supplier,
            $cost
        );
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
        break;
    
    case 'edit':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->maintenanceId, $data->truckId, $data->date, $data->remarks, $data->status)) {
            echo json_encode(["success" => false, "message" => "Incomplete data"]);
            exit;
        }
        
        $licensePlate = isset($data->licensePlate) ? $data->licensePlate : '';
        $supplier = isset($data->supplier) ? $data->supplier : '';
        $cost = isset($data->cost) ? $data->cost : 0;
        
        $stmt = $conn->prepare("UPDATE maintenance SET truck_id = ?, licence_plate = ?, date_mtnce = ?, remarks = ?, 
                               status = ?, supplier = ?, cost = ? WHERE maintenance_id = ?");
        $stmt->bind_param("isssssdi", 
            $data->truckId,
            $licensePlate,
            $data->date, 
            $data->remarks,
            $data->status,
            $supplier,
            $cost,
            $data->maintenanceId
        );
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
        break;
    
    case 'delete':
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
        $stmt->close();
        break;
        
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}

$conn->close();
?>