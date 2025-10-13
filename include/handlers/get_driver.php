<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Check if driver ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Driver ID is required"]);
    exit;
}

$driverId = $_GET['id'];

try {
    // Prepare and execute the query
      $stmt = $conn->prepare("SELECT driver_id, firebase_uid, name, email, contact_no, password, assigned_truck_id, driver_pic, 
                       created_at, last_login, last_modified_by, last_modified_at FROM drivers_table WHERE driver_id = ?");
    $stmt->bind_param("s", $driverId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $driver = $result->fetch_assoc();
        
        // Format created_at properly
        if ($driver['created_at'] !== null) {
            $driver['created_at'] = date('Y-m-d H:i:s', strtotime($driver['created_at']));
        }
        
        // Let's format the modification date too
        if ($driver['last_modified_at'] !== null) {
            $driver['last_modified_at'] = date('Y-m-d H:i:s', strtotime($driver['last_modified_at']));
        }

        // Format last_login properly
        if ($driver['last_login'] !== null && $driver['last_login'] !== 'NULL') {
            $driver['last_login'] = date('Y-m-d H:i:s', strtotime($driver['last_login']));
        }
        
        echo json_encode(["success" => true, "driver" => $driver]);
    } else {
        echo json_encode(["success" => false, "message" => "Driver not found"]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>