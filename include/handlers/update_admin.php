<?php
header("Content-Type: application/json");
require 'dbhandler.php';
require 'phpmailer_config.php'; 



session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Get the ID of the admin performing the change
$actingAdminId = $_SESSION['admin_id'] ?? null;
if (!$actingAdminId) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Admin session not properly initialized."]);
    exit;
}

$adminPic = null;
if (!empty($_FILES['adminProfile']['name'])) {
     if ($_FILES['adminProfile']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => "File is too large (server limit).",
            UPLOAD_ERR_FORM_SIZE => "File is too large (form limit).",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload.",
        ];
        $errorCode = $_FILES['adminProfile']['error'];
        $errorMessage = $uploadErrors[$errorCode] ?? "An unknown upload error occurred.";
        echo json_encode(["success" => false, "message" => $errorMessage]);
        exit;
    }

    if ($_FILES['adminProfile']['size'] > 2 * 1024 * 1024) { // 2MB limit
        echo json_encode(["success" => false, "message" => "File is too large. Maximum size is 2MB."]);
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['adminProfile']['tmp_name']);
    finfo_close($fileInfo);
    
    if (in_array($mimeType, $allowedTypes)) {
        $adminPic = base64_encode(file_get_contents($_FILES['adminProfile']['tmp_name']));
    } else {
        echo json_encode(["success" => false, "message" => "Invalid file type. Only JPG, PNG and GIF are allowed."]);
        exit;
    }
}

$data = $_POST;
if (empty($data)) {
    $data = json_decode(file_get_contents("php://input"), true);
}

// --- Start of Main Logic Update ---

if (!isset($data['admin_id']) || !isset($data['username']) || empty($data['username']) || !isset($data['role']) || empty($data['role']) || !isset($data['admin_email']) || empty($data['admin_email'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Admin ID, username, role, and email are required"]);
    exit;
}

if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

$adminId = $data['admin_id'];

try {
    // 1. Fetch current admin data
    // Fetch username (for email body), password hash, and current email
    $fetchStmt = $conn->prepare("SELECT username, password, admin_email FROM login_admin WHERE admin_id = ?");
    $fetchStmt->bind_param("i", $adminId);
    $fetchStmt->execute();
    $adminData = $fetchStmt->get_result()->fetch_assoc();
    if (!$adminData) {
        throw new Exception("Admin not found.");
    }
    $targetUsername = $adminData['username'];
    $currentEmail = $adminData['admin_email'];
    $fetchStmt->close();
    
    // Fetch the username of the admin performing the change (for email body)
    $actingAdminStmt = $conn->prepare("SELECT username FROM login_admin WHERE admin_id = ?");
    $actingAdminStmt->bind_param("i", $actingAdminId);
    $actingAdminStmt->execute();
    $actingAdminData = $actingAdminStmt->get_result()->fetch_assoc();
    $actingAdminUsername = $actingAdminData['username'] ?? 'Unknown Admin';
    $actingAdminStmt->close();


    // 2. Check for duplicate username or email against OTHER admins
    $checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE (username = ? OR admin_email = ?) AND admin_id != ?");
    $checkStmt->bind_param("ssi", $data['username'], $data['admin_email'], $adminId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception("Username or email already taken by another admin.");
    }
    $checkStmt->close();

    // 3. Determine what is being changed
    $newPassword = $data['password'] ?? '';
    $isPasswordChanging = !empty($newPassword); 
    // Check if the email being set is different from the current email in DB
    $isEmailChanging = (strtolower($data['admin_email']) !== strtolower($currentEmail));

    // --- Prepare for Security Logging and Emailing ---
    $messageType = 'simple_update'; // Default
    $logDetails = [];
    $sampleContactNumber = '+1-800-555-0199'; // Sample contact number

    // --- Start Building the General Update Query ---
    $queryParts = ["UPDATE login_admin SET username = ?, role = ?"];
    $types = "ss";
    $params = [$data['username'], $data['role']];

    if ($adminPic !== null) {
        $queryParts[] = "admin_pic = ?";
        $types .= "s";
        $params[] = $adminPic;
    }
    
    // -------------------------------------------------------------------------
    // 4. PASSWORD CHANGE FLOW 
    // -------------------------------------------------------------------------
    if ($isPasswordChanging) {

         if (strlen($newPassword) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }
        
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
       

       
        $queryParts[] = "password = ?";
        $types .= "s";
        $params[] = $newPasswordHash;
        
       
        $mail = getMailer();
        if (!$mail) {
             error_log("CRITICAL: Mailer failed to initialize for password change notification for admin ID {$adminId}.");
        } else {
             $subject = 'SECURITY ALERT: Admin Password Change Notification';
          
             $body = "Hello,<br><br>
                     The password for your admin account (**{$targetUsername}**) was just changed by another administrator (**{$actingAdminUsername}**).<br><br>
                     <strong style='color: #dc3545;'>This change is now active.</strong><br><br>
                     If this change was authorized, you can safely ignore this email.<br><br>
                     <h3 style='color: #dc3545;'>Security Concern:</h3>
                     If you did **NOT** authorize this change, please contact IT Department immediately at: **{$sampleContactNumber}**.
                     <br><br>Thank you.";
            
            try {
                $mail->clearAllRecipients();
                $mail->addAddress($currentEmail); 
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
            } catch (Exception $e) {
                 error_log("WARNING: Failed to send password change notification email for admin ID {$adminId}. Mailer Error: {$e->getMessage()}");
            }
        }
        
      
        $logDetails[] = "Password changed by Admin ID {$actingAdminId}. alert sent.";
        $messageType = ($isEmailChanging) ? 'password_and_email_notified' : 'password_notified';
    }


    // -------------------------------------------------------------------------
    // 5. EMAIL CHANGE FLOW 
    // -------------------------------------------------------------------------
    if ($isEmailChanging) {
        
        

        
        $logDetails[] = "Email changed from '{$currentEmail}' to '{$data['admin_email']}' by Admin ID {$actingAdminId}. alert sent.";
        $messageType = ($isPasswordChanging) ? 'password_and_email_notified' : 'email_notified';

        
        $mail = getMailer();
        if (!$mail) {
             error_log("CRITICAL: Mailer failed to initialize for email change notification for admin ID {$adminId}.");
        } else {
             $subject = 'SECURITY ALERT: Admin Email Change Notification (Crucial)';
             
             $body = "Hello,<br><br>
                     The email address for your admin account (**{$targetUsername}**) was just changed from **{$currentEmail}** to **{$data['admin_email']}** by another administrator (**{$actingAdminUsername}**).<br><br>
                     <strong style='color: #dc3545;'>This change is now active.</strong><br><br>
                     If this change was authorized, you can safely ignore this email.<br><br>
                     <h3 style='color: #dc3545;'>Security Concern:</h3>
                     If you did **NOT** authorize this change, please contact the IT Department at: **{$sampleContactNumber}**.
                     <br><br>This alert was sent to your OLD email address ({$currentEmail}).
                     <br><br>Thank you.";
            
            try {
                $mail->clearAllRecipients();
                $mail->addAddress($currentEmail); 
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
            } catch (Exception $e) {
                 error_log("WARNING: Failed to send email change notification to OLD email {$currentEmail} for admin ID {$adminId}. Mailer Error: {$e->getMessage()}");
            }
        }
    }


    
    $queryParts[] = "admin_email = ?";
    $types .= "s";
    $params[] = $data['admin_email'];

    $finalQuery = implode(', ', $queryParts);
    $finalQuery .= " WHERE admin_id = ?";
    $types .= "i";
    $params[] = $adminId;


    $stmt = $conn->prepare($finalQuery);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $logAction = "ADMIN_UPDATED_PROFILE";
        if ($isPasswordChanging || $isEmailChanging) {
             $logAction = "ADMIN_UPDATED_SECURE_FIELDS_ALERT_ONLY"; // Updated log action
        }
        
        // Log the security action if any secure field was changed or if a password/email was explicitly logged.
        if (!empty($logDetails)) {
             $logStmt = $conn->prepare("INSERT INTO admin_security_log (admin_id, action, details) VALUES (?, ?, ?)");
             $logDetailsString = implode(' | ', $logDetails);
             $logStmt->bind_param("iss", $adminId, $logAction, $logDetailsString);
             $logStmt->execute();
             $logStmt->close();
        }

        echo json_encode(["success" => true, "message" => "Admin updated successfully.", "message_type" => $messageType]);
    } else {
        throw new Exception("Database update failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
