<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

// Get current username from session
$currentUser = $_SESSION['username'] ?? 'System';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add':
        $stmt = $conn->prepare("INSERT INTO assign 
    (plate_no, date, driver, helper, dispatcher, container_no, client, 
    destination, shippine_line, consignee, size, cash_adv, status,
    last_modified_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssssss",
    $data['plateNo'],
    $data['date'],
    $data['driver'],
    $data['helper'],
    $data['dispatcher'],
    $data['containerNo'],
    $data['client'],
    $data['destination'],
    $data['shippingLine'],
    $data['consignee'],
    $data['size'],
    $data['cashAdvance'],
    $data['status'],
    $currentUser  
);
            $stmt->execute();
            
            // If status is "En Route", update truck status
            if ($data['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE plate_no = ?");
                $updateTruck->bind_param("s", $data['plateNo']);
                $updateTruck->execute();
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'edit':
    // First get current status to check if it's changing
    $getCurrent = $conn->prepare("SELECT status, plate_no FROM assign WHERE trip_id = ?");
    $getCurrent->bind_param("i", $data['id']);
    $getCurrent->execute();
    $current = $getCurrent->get_result()->fetch_assoc();
    
   $stmt = $conn->prepare("UPDATE assign SET 
    plate_no=?, date=?, driver=?, helper=?, dispatcher=?, container_no=?, client=?, 
    destination=?, shippine_line=?, consignee=?, size=?, cash_adv=?, status=?,
    last_modified_by=?
    WHERE trip_id=?");
$stmt->bind_param("ssssssssssssssi",
    $data['plateNo'],
    $data['date'],
    $data['driver'],
    $data['helper'],
    $data['dispatcher'],
    $data['containerNo'],
    $data['client'],
    $data['destination'],
    $data['shippingLine'],
    $data['consignee'],
    $data['size'],
    $data['cashAdvance'],
    $data['status'],
    $currentUser,  
    $data['id']
);
    $stmt->execute();
    
    // Update truck status based on trip status
    if ($current['status'] !== $data['status']) {
        $newTruckStatus = 'Good'; // Default
        
        if ($data['status'] === 'En Route') {
            $newTruckStatus = 'Enroute';
        } elseif ($data['status'] === 'Pending') {
            $newTruckStatus = 'Pending';
        }
        
        $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE plate_no = ?");
        $updateTruck->bind_param("ss", $newTruckStatus, $data['plateNo']);
        $updateTruck->execute();
    }
    
    echo json_encode(['success' => true]);
    break;

        case 'delete':
            // Get plate no before deleting to update truck status
            $getPlate = $conn->prepare("SELECT plate_no, status FROM assign WHERE trip_id = ?");
            $getPlate->bind_param("i", $data['id']);
            $getPlate->execute();
            $trip = $getPlate->get_result()->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM assign WHERE trip_id=?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            
            // If deleted trip was "En Route", set truck back to "Good"
            if ($trip && $trip['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Good' WHERE plate_no = ?");
                $updateTruck->bind_param("s", $trip['plateNo']);
                $updateTruck->execute();
            }
            
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($getCurrent)) $getCurrent->close();
    if (isset($getPlate)) $getPlate->close();
    if (isset($updateTruck)) $updateTruck->close();
    $conn->close();
}
?>