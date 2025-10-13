<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';
require_once 'phpmailer_config.php'; 

date_default_timezone_set('Asia/Manila');

// Define a sample number for the security alert
$sampleContactNumber = '+1-800-555-0199'; 

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}
$actingAdminId = $_SESSION['admin_id'] ?? null;
$actingAdminUsername = $_SESSION['username'] ?? 'Unknown Admin';

// Helper function to log security action
function logDriverSecurityAction($conn, $driverId, $action, $details) {
    try {
        // Use the new driver_security_log table
        $logStmt = $conn->prepare("INSERT INTO driver_security_log (driver_id, action, details) VALUES (?, ?, ?)");
        $logStmt->bind_param("sss", $driverId, $action, $details);
        $logStmt->execute();
        $logStmt->close();
    } catch (Exception $e) {
        // Log to error log if failed to insert into DB
        error_log("Failed to log driver security action for ID {$driverId}: {$e->getMessage()}");
    }
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
        // Since 'add_driver.php' handles this, we can skip this logic block for simplicity, 
        // assuming the client-side code targets add_driver.php correctly.
        // If this block is somehow reached, it should ideally throw an error or redirect.
        throw new Exception("Use add_driver.php endpoint for adding new drivers.");

    } else {
        // --- EDIT MODE LOGIC ---
        
        $driver_id_to_update = $data['driverId'];
        $logDetails = [];
        $messageType = 'simple_update'; // Default
        $isPasswordChanging = !empty($data['password']);
        $isEmailChanging = false; // Determined after fetching current data

        // 1. Fetch current driver data (to check for email change)
        $fetchStmt = $conn->prepare("SELECT email, password, name FROM drivers_table WHERE driver_id = ?");
        $fetchStmt->bind_param("s", $driver_id_to_update);
        $fetchStmt->execute();
        $driverData = $fetchStmt->get_result()->fetch_assoc();
        $fetchStmt->close();

        if (!$driverData) {
            throw new Exception("Driver not found.");
        }
        $currentEmail = $driverData['email'];
        $currentDriverName = $driverData['name'];
        $isEmailChanging = (strtolower($data['email']) !== strtolower($currentEmail));


        // 2. Truck Assignment Check
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
                     throw new Exception("This truck is already assigned to another driver (ID: {$row['driver_id']}).");
                }
            }
            $stmt_truck->close();
        }

        
        // 3. Build Update Query and handle security fields

        $updateFields = [];
        $params = [];
        $types = "";
        
        // Always include name and contact_no
        $updateFields[] = "name = ?";
        $params[] = $data['name'];
        $types .= "s";
        
        $updateFields[] = "contact_no = ?";
        $params[] = $data['contact_no'];
        $types .= "s";

        // Handle Password Change
        if ($isPasswordChanging) {
            // REMOVED: Validation for 'oldPassword' is no longer required for admins.
            // REMOVED: OTP check is no longer required.
            
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= "s";

            // Prepare Security Notification Email (Password)
            $mail = getMailer();
            if ($mail) {
                $subject = 'SECURITY ALERT: Driver Password Change Notification';
                $body = "Hello {$currentDriverName},<br><br>
                         The password for your Driver account (**{$currentDriverName}**) was just changed by an administrator (**{$actingAdminUsername}**).<br><br>
                         <strong style='color: #dc3545;'>This change is now active.</strong><br><br>
                         If this change was authorized, you can safely ignore this email.<br><br>
                         <h3 style='color: #dc3545;'>Security Concern:</h3>
                         If you did **NOT** authorize this change, please Contact the IT department immediately at: **{$sampleContactNumber}**.
                         <br><br>Thank you.";
                
                try {
                    $mail->clearAllRecipients();
                    $mail->addAddress($currentEmail); // Send to the driver's registered email
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->send();
                } catch (Exception $e) {
                     error_log("WARNING: Failed to send password change notification to driver ID {$driver_id_to_update}. Mailer Error: {$e->getMessage()}");
                }
            }
            $logDetails[] = "Password changed by Admin '{$actingAdminUsername}' (ID: {$actingAdminId}). Alert sent to {$currentEmail}.";
            $messageType = 'password_notified';
        }
        
        // Handle Email Change
        if ($isEmailChanging) {
            $newEmail = $data['email'];
            $updateFields[] = "email = ?";
            $params[] = $newEmail;
            $types .= "s";
            
            // Prepare Security Notification Email (Email) - sent to OLD email
            $mail = getMailer();
            if ($mail) {
                 $subject = 'SECURITY ALERT: Driver Email Change Notification (Crucial)';
                 $body = "Hello {$currentDriverName},<br><br>
                         The Email for your driver account (**{$currentDriverName}**) was just changed from **{$currentEmail}** to **{$newEmail}** by an administrator (**{$actingAdminUsername}**).<br><br>
                         <strong style='color: #dc3545;'>This change is now active.</strong><br><br>
                         If this change was authorized, you can safely ignore this email.<br><br>
                         <h3 style='color: #dc3545;'>Security Concern:</h3>
                         If you did **NOT** authorize this change, please contact the IT department immediately at: **{$sampleContactNumber}**.
                         <br><br>This alert was sent to your OLD email address ({$currentEmail}).
                         <br><br>Thank you.";
                
                try {
                    $mail->clearAllRecipients();
                    $mail->addAddress($currentEmail); // Send to the driver's OLD registered email
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->send();
                } catch (Exception $e) {
                     error_log("WARNING: Failed to send email change notification to OLD email {$currentEmail} for driver ID {$driver_id_to_update}. Mailer Error: {$e->getMessage()}");
                }
            }
            $logDetails[] = "Email changed from '{$currentEmail}' to '{$newEmail}' by Admin '{$actingAdminUsername}' (ID: {$actingAdminId}). Alert sent to old email.";
            
            // Update message type flag
            if ($messageType === 'password_notified') {
                 $messageType = 'password_and_email_notified';
            } else {
                 $messageType = 'email_notified';
            }
        } else {
            // If email didn't change, we still ensure the current email is in the params for non-security updates
            $updateFields[] = "email = ?";
            $params[] = $currentEmail;
            $types .= "s";
        }


        // Handle Truck Assignment
        $updateFields[] = "assigned_truck_id = ?";
        $params[] = $assigned_truck_id; 
        $types .= "s";
        
        // Handle Profile Picture
        if ($driverPic !== null) {
            $updateFields[] = "driver_pic = ?";
            $params[] = $driverPic;
            $types .= "s";
        }

        // Keep track of who made the change
        $updateFields[] = "last_modified_by = ?";
        $params[] = $_SESSION['username'];
        $types .= "s";

        // Also, let's not forget to log when this change happened.
        $updateFields[] = "last_modified_at = ?";
        $params[] = date('Y-m-d H:i:s'); // Use PHP's date function with the correct timezone
        $types .= "s";
        
        if (empty($updateFields)) {
            echo json_encode(["success" => true, "message" => "No core fields were changed."]);
            exit;
        }
        
        // 4. Execute the Update
        $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
        $params[] = $driver_id_to_update;
        $types .= "s";
        
        $stmt = $conn->prepare($query);
        
        // Check for bind_param issues before executing
        if (!$stmt) {
             throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            
            // Log security event if needed
            if (!empty($logDetails)) {
                $logAction = "DRIVER_UPDATED_PROFILE";
                if ($isPasswordChanging) $logAction = "ADMIN_UPDATED_DRIVER_PASSWORD_ALERT";
                if ($isEmailChanging) $logAction = "ADMIN_UPDATED_DRIVER_EMAIL_ALERT";
                if ($isPasswordChanging && $isEmailChanging) $logAction = "ADMIN_UPDATED_DRIVER_SECURE_FIELDS_ALERT";

                logDriverSecurityAction($conn, $driver_id_to_update, $logAction, implode(' | ', $logDetails));
            }

            // Update Firebase (using the external function)
            updateFirebaseDriver($driver_id_to_update, $data);
            
            // Send the final success response with the message_type flag
            echo json_encode(["success" => true, "message" => "Driver updated successfully", "message_type" => $messageType]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating driver: " . $stmt->error]);
        }
    }
    
    if(isset($stmt)) $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

function updateFirebaseDriver($driverId, $data) {
    // [The existing updateFirebaseDriver function content remains unchanged]
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
            // IMPORTANT: We use the *new* email here for Firebase
            if (isset($data['email'])) { 
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