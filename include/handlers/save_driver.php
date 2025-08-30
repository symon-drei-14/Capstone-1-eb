<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Handle file upload
$driverPic = null;
if (!empty($_FILES['driverProfile']['name']) && $_FILES['driverProfile']['error'] == UPLOAD_ERR_OK) {
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['driverProfile']['tmp_name']);
    finfo_close($fileInfo);
    
    if (in_array($mimeType, $allowedTypes)) {
        // Read and encode the image
        $driverPic = base64_encode(file_get_contents($_FILES['driverProfile']['tmp_name']));
    } else {
        echo json_encode(["success" => false, "message" => "Invalid file type. Only JPG, PNG and GIF are allowed."]);
        exit;
    }
}

// Get the POST data
$data = $_POST;

// Validate required fields
if (!isset($data['name']) || !isset($data['email'])) {
    echo json_encode(["success" => false, "message" => "Name and email are required"]);
    exit;
}

try {
    if ($data['mode'] === 'add') {
        // Adding a new driver
        $stmt = $conn->prepare("INSERT INTO drivers_table (name, email, password, assigned_truck_id, driver_pic, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        
        $assignedTruck = !empty($data['assignedTruck']) ? $data['assignedTruck'] : null;
        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        
        $stmt->bind_param("sssss", $data['name'], $data['email'], $password, $assignedTruck, $driverPic);
        
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
        
        // Handle password update - only update if password is provided
        if (!empty($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= "s";
        }
        
        if (isset($data['assignedTruck'])) {
            $updateFields[] = "assigned_truck_id = ?";
            $params[] = $data['assignedTruck'] ?: null;
            $types .= "s";
        }
        
        // Handle profile picture update if provided
        if ($driverPic !== null) {
            $updateFields[] = "driver_pic = ?";
            $params[] = $driverPic;
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
            // If password was updated, also update Firebase
            if (!empty($data['password'])) {
                updateFirebaseDriver($data['driverId'], $data);
            }
            echo json_encode(["success" => true, "message" => "Driver updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating driver: " . $stmt->error]);
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

function updateFirebaseDriver($driverId, $data) {
    try {
        $firebase_url = "https://mansartrucking1-default-rtdb.asia-southeast1.firebasedatabase.app/drivers/" . $driverId . ".json?auth=Xtnh1Zva11o8FyDEA75gzep6NUeNJLMZiCK6mXB7";
        
        // Get current data from Firebase first
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $firebase_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $current_data = curl_exec($ch);
        curl_close($ch);
        
        if ($current_data) {
            $firebase_data = json_decode($current_data, true);
            
            // Update only the fields that were changed
            if (!empty($data['name'])) {
                $firebase_data['name'] = $data['name'];
            }
            if (!empty($data['email'])) {
                $firebase_data['email'] = $data['email'];
            }
            if (!empty($data['password'])) {
                $firebase_data['password'] = $data['password']; // Store plain password in Firebase
            }
            if (isset($data['assignedTruck'])) {
                $firebase_data['assigned_truck_id'] = $data['assignedTruck'] ? intval($data['assignedTruck']) : null;
            }
            
            // Update Firebase
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $firebase_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebase_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            curl_exec($ch);
            curl_close($ch);
        }
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Firebase update failed: " . $e->getMessage());
    }
}

$conn->close();
?>