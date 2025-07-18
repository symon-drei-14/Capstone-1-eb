<?php
// Update trip status handler: update_trip_status.php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if required fields are provided
if (!isset($input['trip_id']) || !isset($input['status'])) {
    echo json_encode(["success" => false, "message" => "Trip ID and status are required"]);
    exit;
}

$tripId = $input['trip_id'];
$status = $input['status'];

// Validate status
$validStatuses = ['Pending', 'On-Going', 'En Route', 'Completed', 'No Show', 'Cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status value"]);
    exit;
}

try {
    // First, check if the trip exists
    $checkStmt = $conn->prepare("SELECT trip_id FROM trips WHERE trip_id = ?");
    $checkStmt->bind_param("i", $tripId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Trip not found"]);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // Update the trip status
    $updateStmt = $conn->prepare("UPDATE trips SET status = ? WHERE trip_id = ?");
    $updateStmt->bind_param("si", $status, $tripId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode([
                "success" => true, 
                "message" => "Trip status updated successfully",
                "trip_id" => $tripId,
                "new_status" => $status
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "No changes made to trip status"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update trip status"]);
    }

    $updateStmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>