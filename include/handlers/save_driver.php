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

// Validate required fields
if (!isset($data['name']) || !isset($data['email'])) {
    echo json_encode(["success" => false, "message" => "Name and email are required"]);
    exit;
}

try {
    if ($data['mode'] === 'add') {
        // Adding a new driver
        $stmt = $conn->prepare("INSERT INTO drivers_table (name, email, firebase_uid, password, assigned_truck_id, created_at, last_login) 
                               VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        
        $lastLogin = !empty($data['lastLogin']) ? $data['lastLogin'] : null;
        $assignedTruck = !empty($data['assignedTruck']) ? $data['assignedTruck'] : null;
        $firebaseUid = !empty($data['firebaseUid']) ? $data['firebaseUid'] : null;
        $password = !empty($data['password']) ? $data['password'] : null;
        
        $stmt->bind_param("ssssss", $data['name'], $data['email'], $firebaseUid, $password, $assignedTruck, $lastLogin);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Driver added successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error adding driver: " . $stmt->error]);
        }
    } else {
        // Updating an existing driver
        $updateFields = [];
        $params = [];
        $types = "";
        
        // Build the update query dynamically based on which fields are provided
        if (!empty($data['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $data['name'];
            $types .= "s";
        }
        
        if (!empty($data['email'])) {
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
            $types .= "s";
        }
        
        if (isset($data['firebaseUid'])) {
            $updateFields[] = "firebase_uid = ?";
            $params[] = $data['firebaseUid'] ?: null;
            $types .= "s";
        }
        
        if (isset($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = $data['password'] ?: null;
            $types .= "s";
        }
        
        if (isset($data['assignedTruck'])) {
            $updateFields[] = "assigned_truck_id = ?";
            $params[] = $data['assignedTruck'] ?: null;
            $types .= "s";
        }
        
        if (isset($data['lastLogin'])) {
            $updateFields[] = "last_login = ?";
            $params[] = $data['lastLogin'] ?: null;
            $types .= "s";
        }
        
        if (empty($updateFields)) {
            echo json_encode(["success" => false, "message" => "No fields to update"]);
            exit;
        }
        
        $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
        $params[] = $data['driverId'];
        $types .= "s";
        
        $stmt = $conn->prepare($query);
        
        // Dynamically bind parameters
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Driver updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating driver: " . $stmt->error]);
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>