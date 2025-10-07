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
if (!isset($data['name']) || !isset($data['email']) || !isset($data['contact_no'])) {
    echo json_encode(["success" => false, "message" => "Name, email, and contact number are required"]);
    exit;
}

try {
    if ($data['mode'] === 'add') {
        // This block is retained for logical consistency but is handled by add_driver.php
        $stmt = $conn->prepare("INSERT INTO drivers_table (name, email, contact_no, password, assigned_truck_id, driver_pic, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        $assignedTruck = !empty($data['assigned_truck_id']) ? $data['assigned_truck_id'] : null;
        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        
        $stmt->bind_param("ssssss", $data['name'], $data['email'], $data['contact_no'], $password, $assignedTruck, $driverPic);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Driver added successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error adding driver: " . $stmt->error]);
        }
    } else {
        // For edit mode, validate old password if new password is provided
        if (!empty($data['password'])) {
            // A 'Full Admin' can change a driver's password without knowing the old one.
            $userRole = $_SESSION['role'] ?? '';
            $canBypassOldPassword = in_array($userRole, ['Full Admin', 'Operations Manager']);
            
            // FIX: The variable was incorrect here.
            if (!$canBypassOldPassword) {
                if (empty($data['oldPassword'])) {
                    echo json_encode(["success" => false, "message" => "Current password is required to set a new one."]);
                    exit;
                }
                
                // Verify old password if not a Full Admin
                $checkPassword = "SELECT password FROM drivers_table WHERE driver_id = ?";
                $stmtCheck = $conn->prepare($checkPassword);
                $stmtCheck->bind_param("s", $data['driverId']);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                
                if ($resultCheck && $resultCheck->num_rows > 0) {
                    $driverData = $resultCheck->fetch_assoc();
                    if (!password_verify($data['oldPassword'], $driverData['password'])) {
                        echo json_encode(["success" => false, "message" => "Current password is incorrect"]);
                        exit;
                    }
                }
                $stmtCheck->close();
            }
        }
        
        $driver_id_to_update = $data['driverId'];
        $assigned_truck_id = $data['assigned_truck_id'] ?: null;

        if ($assigned_truck_id !== null) {
            // Check if the truck is already assigned to a DIFFERENT driver
            $check_truck = "SELECT driver_id FROM drivers_table WHERE assigned_truck_id = ?";
            $stmt_truck = $conn->prepare($check_truck);
            if (!$stmt_truck) {
                throw new Exception("Truck check prepare failed: " . $conn->error);
            }
            $stmt_truck->bind_param("s", $assigned_truck_id);
            $stmt_truck->execute();
            $result_truck = $stmt_truck->get_result();
            
            if ($result_truck->num_rows > 0) {
                $row = $result_truck->fetch_assoc();
                // If the truck is assigned, check if it's to someone other than the current driver
                if ($row['driver_id'] != $driver_id_to_update) {
                     throw new Exception("This truck is already assigned to another driver.");
                }
            }
            $stmt_truck->close();
        }
        
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
        
        if (!empty($data['contact_no'])) {
            $updateFields[] = "contact_no = ?";
            $params[] = $data['contact_no'];
            $types .= "s";
        }
        
        if (!empty($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= "s";
        }
        
        if (isset($data['assigned_truck_id'])) {
            $updateFields[] = "assigned_truck_id = ?";
            $params[] = $assigned_truck_id; // Use the validated variable
            $types .= "s";
        }
        
        if ($driverPic !== null) {
            $updateFields[] = "driver_pic = ?";
            $params[] = $driverPic;
            $types .= "s";
        }
        
        if (empty($updateFields)) {
            echo json_encode(["success" => true, "message" => "No fields were changed."]);
            exit;
        }
        
        $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
        $params[] = $data['driverId'];
        $types .= "s";
        
        $stmt = $conn->prepare($query);
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            updateFirebaseDriver($data['driverId'], $data);
            echo json_encode(["success" => true, "message" => "Driver updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating driver: " . $stmt->error]);
        }
    }
    
    if(isset($stmt)) $stmt->close();
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
            
            if (!empty($data['name'])) {
                $firebase_data['name'] = $data['name'];
            }
            if (!empty($data['email'])) {
                $firebase_data['email'] = $data['email'];
            }
            // The password field in Firebase is no longer needed, so we won't update it.
            if (isset($data['assigned_truck_id'])) {
                $assigned_truck_id = $data['assigned_truck_id'] ?: null;
                $firebase_data['assigned_truck_id'] = $assigned_truck_id ? intval($assigned_truck_id) : null;
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
        error_log("Firebase update failed: " . $e->getMessage());
    }
}

$conn->close();
?>