<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

try {
    // Prepare and execute the query to get all drivers
    $stmt = $conn->prepare("SELECT driver_id, name, email, assigned_truck_id, created_at, last_login FROM drivers_table");
    $stmt->execute();
    $result = $stmt->get_result();

    $drivers = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format created_at properly
            if ($row['created_at'] !== null) {
                $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
            }
            
            // Format last_login properly
            if ($row['last_login'] !== null && $row['last_login'] !== 'NULL') {
                $row['last_login'] = date('Y-m-d H:i:s', strtotime($row['last_login']));
            } else {
                $row['last_login'] = 'Never';
            }
            
            $drivers[] = $row;
        }
        
        echo json_encode(["success" => true, "drivers" => $drivers]);
    } else {
        echo json_encode(["success" => true, "drivers" => []]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>