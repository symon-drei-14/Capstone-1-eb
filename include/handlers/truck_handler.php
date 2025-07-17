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
    
    if ($tripStatus === 'En Route') {
        $newStatus = 'Enroute';
    } elseif ($maintenanceStatus === 'In Progress') {
        $newStatus = 'In Repair';
    } elseif ($maintenanceStatus === 'Overdue') {
        $newStatus = 'Overdue';
    } elseif (($maintenanceStatus === 'Pending' || $maintenanceStatus === 'Completed') && 
              ($tripStatus === 'Pending' || $tripStatus === 'Completed' || $tripStatus === null)) {
        $newStatus = 'In Terminal';
    }
    
    // Update the truck status
    $updateStmt = $conn->prepare("UPDATE truck_table SET status = ? WHERE truck_id = ?");
    $updateStmt->bind_param("si", $newStatus, $truckId);
    $updateStmt->execute();
}

try {
    switch ($action) {
        case 'getTrucks':
          $stmt = $conn->prepare("SELECT t.truck_id, t.plate_no, t.capacity, 
                       COALESCE(
                           (SELECT a.status FROM assign a 
                            WHERE a.plate_no = t.plate_no 
                            AND a.status IN ('En Route', 'Pending')
                            ORDER BY a.date DESC LIMIT 1
                           ), 
                           t.status
                       ) as display_status,
                       t.last_modified_by, 
                       t.last_modified_at
                       FROM truck_table t
                       ORDER BY t.truck_id");
            $stmt->execute();
            $result = $stmt->get_result();
            $trucks = $result->fetch_all(MYSQLI_ASSOC);
            
            // Update status for each truck based on maintenance and trip logs
            foreach ($trucks as &$truck) {
                updateTruckStatus($conn, $truck['truck_id'], $truck['plate_no']);
            }
            
            // Re-fetch to get updated statuses
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

            $stmt = $conn->prepare("UPDATE truck_table 
                                  SET plate_no=?, capacity=?, status=?, 
                                  last_modified_by=? 
                                  WHERE truck_id=?");
            $stmt->bind_param("ssssi", 
                $data['plate_no'], 
                $data['capacity'], 
                $data['status'],
                $currentUser,
                $data['truck_id']
            );
            $stmt->execute();
            
            // Update truck status based on maintenance and trip logs
            updateTruckStatus($conn, $data['truck_id'], $data['plate_no']);
            
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