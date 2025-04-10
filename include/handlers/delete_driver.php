<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['driverId'])) {
    echo json_encode(["success" => false, "message" => "Driver ID is required"]);
    exit;
}

$driverId = $data['driverId'];

try {
    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM drivers_table WHERE driver_id = ?");
    $stmt->bind_param("s", $driverId);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Driver deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting driver: " . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>