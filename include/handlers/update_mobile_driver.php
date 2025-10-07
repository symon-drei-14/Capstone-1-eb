<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-control-allow-methods: POST, OPTIONS");
header("Access-control-allow-headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'dbhandler.php';
require_once 'phpmailer_config.php'; // For sending emails
session_start();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    if (!isset($_POST['driver_id'])) {
        throw new Exception("Driver ID is required.");
    }
    $driver_id = $_POST['driver_id'];

    // Fetch current driver data to check against
    $fetchStmt = $conn->prepare("SELECT name, email, password FROM drivers_table WHERE driver_id = ?");
    $fetchStmt->bind_param("s", $driver_id);
    $fetchStmt->execute();
    $driverData = $fetchStmt->get_result()->fetch_assoc();
    if (!$driverData) {
        throw new Exception("Driver not found.");
    }
    $currentEmail = $driverData['email'];
    $currentHash = $driverData['password'];
    $currentName = $driverData['name'];
    $fetchStmt->close();

    $isPasswordChanging = !empty($_POST['password']);
    $isEmailChanging = isset($_POST['email']) && trim($_POST['email']) !== $currentEmail;
    
    // This is the second step: verifying the submitted OTP
    if (isset($_POST['otp'])) {
        if (!isset($_SESSION['otp_data']) || $_SESSION['otp_data']['driver_id'] != $driver_id) {
            throw new Exception("OTP process not initiated or session expired.");
        }
        if ($_SESSION['otp_data']['otp'] != $_POST['otp']) {
            throw new Exception("The OTP you entered is incorrect.");
        }
        if (time() > $_SESSION['otp_data']['expiry']) {
            unset($_SESSION['otp_data']);
            throw new Exception("OTP has expired. Please try again.");
        }

        // OTP is valid. Get pending changes from the session.
        $pendingData = $_SESSION['otp_data'];
        unset($_SESSION['otp_data']); // Clean up session

        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($pendingData['name'])) { $updateFields[] = "name = ?"; $params[] = $pendingData['name']; $types .= "s"; }
        if (isset($pendingData['new_email'])) { $updateFields[] = "email = ?"; $params[] = $pendingData['new_email']; $types .= "s"; }
        if (isset($pendingData['contact_no'])) { $updateFields[] = "contact_no = ?"; $params[] = $pendingData['contact_no']; $types .= "s"; }
        if (isset($pendingData['new_password'])) { $updateFields[] = "password = ?"; $params[] = $pendingData['new_password']; $types .= "s"; }
        if (isset($pendingData['driver_pic'])) { $updateFields[] = "driver_pic = ?"; $params[] = $pendingData['driver_pic']; $types .= "s"; }

        if (empty($updateFields)) {
            throw new Exception("No pending changes found to apply.");
        }
        
        $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
        $params[] = $driver_id;
        $types .= "s";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Database update failed after OTP verification.");
        }
        $stmt->close();
        
    // This is the first step: initiating an update that requires an OTP
    } else if ($isPasswordChanging || $isEmailChanging) {
        // If password is changing, the old password must be verified first
        if ($isPasswordChanging) {
            if (empty($_POST['old_password'])) {
                throw new Exception("Current password is required to set a new one.");
            }
            if (!password_verify($_POST['old_password'], $currentHash)) {
                throw new Exception("Incorrect current password.");
            }
        }
        // If email is changing, check if the new one is already taken
        if ($isEmailChanging) {
            $check_email = $conn->prepare("SELECT driver_id FROM drivers_table WHERE email = ? AND driver_id != ?");
            $check_email->bind_param("ss", $_POST['email'], $driver_id);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                throw new Exception("Email is already registered to another driver.");
            }
            $check_email->close();
        }

        $otp = rand(100000, 999999);
        $sessionData = [
            'otp' => $otp,
            'expiry' => time() + 300, // OTP valid for 5 minutes
            'driver_id' => $driver_id,
        ];
        
        // Store all potential pending changes in the session
        if (isset($_POST['name'])) { $sessionData['name'] = trim($_POST['name']); }
        if (isset($_POST['contact_no'])) { $sessionData['contact_no'] = trim($_POST['contact_no']); }
        if ($isEmailChanging) { $sessionData['new_email'] = trim($_POST['email']); }
        if ($isPasswordChanging) { $sessionData['new_password'] = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); }
        if (isset($_FILES['driver_pic']) && $_FILES['driver_pic']['error'] == UPLOAD_ERR_OK) {
             $sessionData['driver_pic'] = base64_encode(file_get_contents($_FILES['driver_pic']['tmp_name']));
        }

        $_SESSION['otp_data'] = $sessionData;

        $mail = getMailer();
        if (!$mail) {
            throw new Exception("Mail server is not configured correctly.");
        }

        $subject = 'Your Account Security Verification';
        $body = "Hello {$currentName},<br><br>A request was made to update your account details. ";
        $body .= "Please use the following verification code to confirm the changes:<br><br><b>{$otp}</b><br><br>";
        $body .= "This code will expire in 5 minutes.<br><br>If you did not request this, you can safely ignore this email.<br><br>Thank you.";
        
        $mail->addAddress($currentEmail, $currentName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();

        echo json_encode(["success" => true, "otp_required" => true, "message" => "An OTP has been sent to your current email address."]);
        exit;
        
    } else {
        // This handles updates without a password or email change (e.g., just name, contact no, or profile pic)
        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($_POST['name'])) { $updateFields[] = "name = ?"; $params[] = trim($_POST['name']); $types .= "s"; }
        if (isset($_POST['contact_no'])) { $updateFields[] = "contact_no = ?"; $params[] = trim($_POST['contact_no']); $types .= "s"; }
        if (isset($_FILES['driver_pic']) && $_FILES['driver_pic']['error'] == UPLOAD_ERR_OK) {
             $updateFields[] = "driver_pic = ?"; $params[] = base64_encode(file_get_contents($_FILES['driver_pic']['tmp_name'])); $types .= "s";
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => true, 'message' => 'No changes were made.']);
            exit;
        }

        $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
        $params[] = $driver_id;
        $types .= "s";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Database update failed for non-sensitive data.");
        }
        $stmt->close();
    }

    // After a successful update, fetch the latest data to return
    $select_stmt = $conn->prepare("SELECT name, email, contact_no, driver_pic FROM drivers_table WHERE driver_id = ?");
    $select_stmt->bind_param("s", $driver_id);
    $select_stmt->execute();
    $updated_driver_data = $select_stmt->get_result()->fetch_assoc();
    $select_stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Driver information updated successfully',
        'updated_driver' => $updated_driver_data
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>