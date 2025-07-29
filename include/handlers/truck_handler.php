<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

// Get the current username from session or default to 'System'
$currentUser = $_SESSION['username'] ?? 'System';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? $_GET['action'] ?? '';

function validatePlateNumber($plateNo) {
    return preg_match("/^[A-Za-z]{2,3}-?\d{3,4}$/", $plateNo);
}



// Function to update truck status based on maintenance and trip logs
function updateTruckStatus($conn, $truckId, $plateNo) {
    // Check maintenance status
    $maintenanceStatus = null;
    $maintenanceQuery = $conn->prepare("SELECT status FROM maintenance WHERE truck_id = ? ORDER BY date_mtnce DESC LIMIT 1");
    $maintenanceQuery->bind_param("i", $truckId);
    $maintenanceQuery->execute();
    $maintenanceResult = $maintenanceQuery->get_result();
    if ($maintenanceResult->num_rows > 0) {
        $maintenanceStatus = $maintenanceResult->fetch_assoc()['status'];
    }
    
    // Check trip status
    $tripStatus = null;
    $tripQuery = $conn->prepare("SELECT status FROM assign WHERE plate_no = ? ORDER BY date DESC LIMIT 1");
    $tripQuery->bind_param("s", $plateNo);
    $tripQuery->execute();
    $tripResult = $tripQuery->get_result();
    if ($tripResult->num_rows > 0) {
        $tripStatus = $tripResult->fetch_assoc()['status'];
    }
    
    // Determine the new status based on the rules
    $newStatus = 'In Terminal'; // Default status
    
    if ($tripStatus === 'Enroute') {
        $newStatus = 'Enroute';
    } elseif ($maintenanceStatus === 'In Progress') {
        $newStatus = 'In Repair';
    } elseif ($maintenanceStatus === 'Overdue') {
        $newStatus = 'Overdue';
    }
    // For all other cases (Pending, Completed, or null), status remains 'In Terminal'
    
    // Update the truck status
    $updateStmt = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
    $updateStmt->bind_param("si", $newStatus, $truckId);
    $updateStmt->execute();
}

try {
    switch ($action) {
 case 'getTrucks':
    $stmt = $conn->prepare("SELECT t.truck_id, t.plate_no, t.capacity, 
                          t.status as display_status, t.is_deleted,
                          t.last_modified_by, t.delete_reason,
                          t.last_modified_at
                          FROM truck_table t
                          ORDER BY t.truck_id");
    $stmt->execute();
    $result = $stmt->get_result();
    $trucks = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'trucks' => $trucks]);
    break;
        case 'addTruck':
            if (!validatePlateNumber($data['plate_no'])) {
                throw new Exception("Invalid plate number format. Use format like ABC123 or ABC-1234");
            }

            $stmt = $conn->prepare("INSERT INTO truck_table 
                                  (plate_no, capacity, status, last_modified_by) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", 
                $data['plate_no'], 
                $data['capacity'], 
                $data['status'],
                $currentUser
            );
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'updateTruck':
    if (!validatePlateNumber($data['plate_no'])) {
        throw new Exception("Invalid plate number format. Use format like ABC123 or ABC-1234");
    }

    // Validate status
    $validStatuses = ['In Terminal', 'Enroute', 'In Repair', 'Overdue'];
    if (!in_array($data['status'], $validStatuses)) {
        throw new Exception("Invalid status value");
    }

    $stmt = $conn->prepare("UPDATE truck_table 
                          SET plate_no=?, capacity=?, status=?, 
                          last_modified_by=?, last_modified_at=NOW()
                          WHERE truck_id=?");
    $stmt->bind_param("ssssi", 
        $data['plate_no'], 
        $data['capacity'], 
        $data['status'],
        $currentUser,
        $data['truck_id']
    );
    $stmt->execute();
    
    
    echo json_encode(['success' => true]);
    break;

  case 'deleteTruck':
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete the truck
        $stmt = $conn->prepare("DELETE FROM truck_table WHERE truck_id=?");
        $stmt->bind_param("i", $data['truck_id']);
        $stmt->execute();
        
        // Get the maximum remaining truck_id
        $maxIdResult = $conn->query("SELECT MAX(truck_id) as max_id FROM truck_table");
        $maxId = $maxIdResult->fetch_assoc()['max_id'];
        
        // Set the auto-increment to max_id + 1
        if ($maxId) {
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = " . ($maxId + 1));
        } else {
            // If no trucks left, reset to 1
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = 1");
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'getActiveTrucks':
    $stmt = $conn->prepare("SELECT t.truck_id, t.plate_no, t.capacity, 
                          t.status as display_status
                          FROM truck_table t
                          WHERE t.is_deleted = 0
                          ORDER BY t.truck_id");
    $stmt->execute();
    $result = $stmt->get_result();
    $trucks = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'trucks' => $trucks]);
    break;

    case 'fullDeleteTruck':
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete the truck
        $stmt = $conn->prepare("DELETE FROM truck_table WHERE truck_id=?");
        $stmt->bind_param("i", $data['truck_id']);
        $stmt->execute();
        
        // Get the maximum remaining truck_id
        $maxIdResult = $conn->query("SELECT MAX(truck_id) as max_id FROM truck_table");
        $maxId = $maxIdResult->fetch_assoc()['max_id'];
        
        // Set the auto-increment to max_id + 1
        if ($maxId) {
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = " . ($maxId + 1));
        } else {
            // If no trucks left, reset to 1
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = 1");
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

case 'softDeleteTruck':
    $stmt = $conn->prepare("UPDATE truck_table 
                          SET is_deleted=1, status='Deleted', delete_reason=?, 
                          last_modified_by=?, last_modified_at=NOW()
                          WHERE truck_id=?");
    $stmt->bind_param("ssi", 
        $data['delete_reason'],
        $currentUser,
        $data['truck_id']
    );
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

    case 'restoreTruck':
    // First get the truck's previous status before deletion
    $getStmt = $conn->prepare("SELECT status FROM truck_table WHERE truck_id = ?");
    $getStmt->bind_param("i", $data['truck_id']);
    $getStmt->execute();
    $result = $getStmt->get_result();
    $truck = $result->fetch_assoc();
    
    // Determine the status to restore to
    $statusToRestore = 'In Terminal'; // default if no status found
    if ($truck && isset($truck['status'])) {
        // If the status was 'Deleted' (from soft delete), restore to default
        $statusToRestore = ($truck['status'] === 'Deleted') ? 'In Terminal' : $truck['status'];
    }
    
    // Now update the truck
    $stmt = $conn->prepare("UPDATE truck_table 
                          SET is_deleted=0, delete_reason=NULL, status=?,
                          last_modified_by=?, last_modified_at=NOW()
                          WHERE truck_id=?");
    $stmt->bind_param("ssi", 
        $statusToRestore,
        $currentUser,
        $data['truck_id']
    );
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>