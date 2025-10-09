<?php
header("Content-Type: application/json");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'dbhandler.php';
require_once 'phpmailer_config.php'; // Needed for sending OTP email

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Ensure an admin is logged in (security check)
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$currentAdminId = $_SESSION['admin_id'];

try {
    if (!isset($_POST['adminId']) || (int)$_POST['adminId'] !== $currentAdminId) {
        throw new Exception("Invalid admin ID or session mismatch.");
    }
    
    // --- Step 1: Initial Fetch of Current Data ---
    $fetchStmt = $conn->prepare("SELECT username, admin_email, password, admin_pic, role FROM login_admin WHERE admin_id = ?");
    $fetchStmt->bind_param("i", $currentAdminId);
    $fetchStmt->execute();
    $adminData = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    if (!$adminData) {
        throw new Exception("Admin account not found.");
    }
    
    $currentEmail = $adminData['admin_email'];
    $currentHash = $adminData['password'];
    $currentUsername = $adminData['username'];

    // --- Step 2: Determine Changes ---
    $newUsername = trim($_POST['username'] ?? $currentUsername);
    $newEmail = trim($_POST['adminEmail'] ?? $currentEmail);
    $oldPassword = trim($_POST['oldPassword'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');

    $isNameChanging = $newUsername !== $currentUsername;
    $isEmailChanging = $newEmail !== $currentEmail;
    $isPasswordChanging = !empty($newPassword);
    $isPhotoChanging = isset($_FILES['adminProfile']) && $_FILES['adminProfile']['error'] == UPLOAD_ERR_OK;

    // OTP is required if EITHER email or password changes
    $requiresOtp = $isEmailChanging || $isPasswordChanging;
    
    // Current Password is ONLY required if password changes
    $requiresOldPasswordVerification = $isPasswordChanging;

    $hasAnyChange = $isNameChanging || $isEmailChanging || $isPasswordChanging || $isPhotoChanging;
    
    if (!$hasAnyChange) {
         echo json_encode(['success' => true, 'message' => 'No changes were detected.']);
         exit;
    }

    // --- Step 3: Handle OTP Verification (Second Request) ---
    if (isset($_POST['otp'])) {
        if (!isset($_SESSION['profile_otp_data']) || $_SESSION['profile_otp_data']['admin_id'] != $currentAdminId) {
            throw new Exception("OTP verification process expired or invalid session.");
        }
        
        $submittedOtp = $_POST['otp'];
        $pendingData = $_SESSION['profile_otp_data'];
        
        if ($pendingData['otp'] != $submittedOtp) {
             throw new Exception("The verification code is incorrect.");
        }
        if (time() > $pendingData['expiry']) {
            unset($_SESSION['profile_otp_data']);
            throw new Exception("The verification code has expired. Please try again.");
        }

        // OTP is valid. Apply pending changes.
        unset($_SESSION['profile_otp_data']); 
        
        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($pendingData['username'])) { $updateFields[] = "username = ?"; $params[] = $pendingData['username']; $types .= "s"; }
        if (isset($pendingData['new_email'])) { $updateFields[] = "admin_email = ?"; $params[] = $pendingData['new_email']; $types .= "s"; }
        if (isset($pendingData['new_password'])) { $updateFields[] = "password = ?"; $params[] = $pendingData['new_password']; $types .= "s"; }
        if (isset($pendingData['admin_pic'])) { $updateFields[] = "admin_pic = ?"; $params[] = $pendingData['admin_pic']; $types .= "s"; }

        $query = "UPDATE login_admin SET " . implode(", ", $updateFields) . " WHERE admin_id = ?";
        $params[] = $currentAdminId;
        $types .= "i";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Database update failed after OTP verification: " . $conn->error);
        }
        $stmt->close();
        
        // Update session if username or photo changed
      if (isset($pendingData['username'])) $_SESSION['username'] = $pendingData['username'];
        if (isset($pendingData['admin_pic'])) $_SESSION['admin_pic'] = $pendingData['admin_pic'];


        $response = [
            'success' => true,
            'message' => 'Profile updated and verified successfully!'
        ];
        if (isset($pendingData['username'])) {
            $response['updated_username'] = $pendingData['username'];
        }
        if (isset($pendingData['admin_pic'])) {
            $response['updated_photo_base64'] = $pendingData['admin_pic'];
        }

        echo json_encode($response);
        exit;
    }

    // --- Step 4: Handle Initial Update Request (First Request) ---
    
    // Check if new email is already taken before processing
    if ($isEmailChanging) {
        $check_email = $conn->prepare("SELECT admin_id FROM login_admin WHERE admin_email = ? AND admin_id != ?");
        $check_email->bind_param("si", $newEmail, $currentAdminId);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            throw new Exception("Email is already used by another admin.");
        }
        $check_email->close();
    }
    
    // --- Security Checks for Sensitive Changes ---
    if ($requiresOtp) {
        
        // ONLY check old password if the user is changing their password
        if ($requiresOldPasswordVerification) {
            if (empty($oldPassword)) {
                // This client-side check is technically redundant if the client-side works, 
                // but kept for server-side validation integrity.
                throw new Exception("Current password is required to change your password."); 
            }
            if (!password_verify($oldPassword, $currentHash)) {
                throw new Exception("Incorrect current password for verification.");
            }
        }
        
        // Initiate OTP flow
        $otp = rand(100000, 999999);
        $sessionData = [
            'otp' => $otp,
            'expiry' => time() + 300, // OTP valid for 5 minutes
            'admin_id' => $currentAdminId,
        ];
        
        // Store all potential changes in the session for later application
        if ($isNameChanging) { $sessionData['username'] = $newUsername; }
        if ($isEmailChanging) { $sessionData['new_email'] = $newEmail; }
        if ($isPasswordChanging) { $sessionData['new_password'] = password_hash($newPassword, PASSWORD_DEFAULT); }
        if ($isPhotoChanging) {
             $sessionData['admin_pic'] = base64_encode(file_get_contents($_FILES['adminProfile']['tmp_name']));
        }

        $_SESSION['profile_otp_data'] = $sessionData;

        $mail = getMailer();
        if (!$mail) {
            throw new Exception("Mail server is not configured correctly.");
        }

        $subject = 'Admin Profile Security Verification Code';
        $body = "Hello {$currentUsername},<br><br>A request was made to update your account details. ";
        $body .= "Please use the following verification code to confirm the changes:<br><br><b>{$otp}</b><br><br>";
        $body .= "This code will expire in 5 minutes.<br><br>If you did not request this, please contact IT immediately.<br><br>Thank you.";
        
        $mail->addAddress($currentEmail, $currentUsername); // Send OTP to current registered email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        if (!$mail->send()) {
            // Clean up session data if mail fails
            unset($_SESSION['profile_otp_data']);
            error_log("OTP Mail failure: " . $mail->ErrorInfo);
            throw new Exception("Failed to send verification email. Please try again later.");
        }

        echo json_encode(["success" => true, "otp_required" => true, "message" => "A verification code has been sent to your registered email."]);
        exit;
        
    } else {
        
        // --- Step 5: Handle Non-Sensitive Updates (No OTP needed) ---
        // This path is used if only name and/or photo are changing.
        $updateFields = [];
        $params = [];
        $types = "";

        if ($isNameChanging) { $updateFields[] = "username = ?"; $params[] = $newUsername; $types .= "s"; }
        if ($isPhotoChanging) { 
             $updateFields[] = "admin_pic = ?"; 
             $photoData = base64_encode(file_get_contents($_FILES['adminProfile']['tmp_name']));
             $params[] = $photoData; 
             $types .= "s";
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => true, 'message' => 'No relevant changes to save.']);
            exit;
        }

        $query = "UPDATE login_admin SET " . implode(", ", $updateFields) . " WHERE admin_id = ?";
        $params[] = $currentAdminId;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Database update failed for non-sensitive data: " . $conn->error);
        }
        $stmt->close();
        
        // Update session immediately for non-sensitive changes
         if ($isNameChanging) $_SESSION['username'] = $newUsername;
        if ($isPhotoChanging) $_SESSION['admin_pic'] = $photoData;

        $response = [
            'success' => true,
            'message' => 'Profile updated successfully.'
        ];

        // Add the new username to the response IF it was changed
        if ($isNameChanging) {
            $response['updated_username'] = $newUsername;
        }
        // Add the new photo to the response IF it was changed
        if ($isPhotoChanging) {
            $response['updated_photo_base64'] = $photoData;
        }

        // Send the new data back to the JavaScript
        echo json_encode($response);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>