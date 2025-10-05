<?php
header("Content-Type: application/json");
require 'dbhandler.php';
require 'phpmailer_config.php'; // Required for sending OTP emails

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
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
    // Fetch current admin data to get the current email for verification
    $fetchStmt = $conn->prepare("SELECT password, admin_email FROM login_admin WHERE admin_id = ?");
    $fetchStmt->bind_param("i", $adminId);
    $fetchStmt->execute();
    $adminData = $fetchStmt->get_result()->fetch_assoc();
    if (!$adminData) {
        throw new Exception("Admin not found.");
    }
    $currentHash = $adminData['password'];
    $currentEmail = $adminData['admin_email'];
    $fetchStmt->close();

    // Check if the new username or new email already exists for another admin
    $checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE (username = ? OR admin_email = ?) AND admin_id != ?");
    $checkStmt->bind_param("ssi", $data['username'], $data['admin_email'], $adminId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception("Username or email already taken by another admin.");
    }
    $checkStmt->close();

    // Determine what is being changed
    $isPasswordChanging = !empty($data['password']);
    $isEmailChanging = ($data['admin_email'] !== $currentEmail);

    // If password or email is being changed, we need OTP verification.
    if ($isPasswordChanging || $isEmailChanging) {
        
        // This is the second step: Verifying the submitted OTP
        if (isset($data['otp'])) {
            if (!isset($_SESSION['otp_data']) || $_SESSION['otp_data']['admin_id'] != $adminId) {
                throw new Exception("OTP process not initiated or session expired.");
            }
            if ($_SESSION['otp_data']['otp'] != $data['otp']) {
                throw new Exception("The OTP you entered is incorrect.");
            }
            if (time() > $_SESSION['otp_data']['expiry']) {
                unset($_SESSION['otp_data']);
                throw new Exception("OTP has expired. Please try again.");
            }

            // OTP is valid. Get the pending changes from the session.
            $newPasswordHash = $_SESSION['otp_data']['new_password'] ?? null;
            $newEmail = $_SESSION['otp_data']['new_email'] ?? $currentEmail;
            unset($_SESSION['otp_data']); // Clean up session

            // Build the final update query
            $query = "UPDATE login_admin SET username = ?, role = ?, admin_email = ?";
            $types = "sss";
            $params = [$data['username'], $data['role'], $newEmail];

            if ($newPasswordHash) {
                $query .= ", password = ?";
                $types .= "s";
                $params[] = $newPasswordHash;
            }
            if ($adminPic !== null) {
                $query .= ", admin_pic = ?";
                $types .= "s";
                $params[] = $adminPic;
            }
            $query .= " WHERE admin_id = ?";
            $types .= "i";
            $params[] = $adminId;

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Admin updated successfully."]);
            } else {
                throw new Exception("Database update failed.");
            }
            $stmt->close();

        } else {
            // This is the first step: Generating and sending the OTP
            if ($isPasswordChanging) {
                if (empty($data['old_password'])) {
                    throw new Exception("Current password is required to set a new one.");
                }
                if (!password_verify($data['old_password'], $currentHash)) {
                    throw new Exception("Incorrect current password.");
                }
            }

            $otp = rand(100000, 999999);
            
            // Store all pending changes in the session with the OTP
            $sessionData = [
                'otp' => $otp,
                'expiry' => time() + 300, // OTP valid for 5 minutes
                'admin_id' => $adminId,
            ];
            if ($isPasswordChanging) {
                $sessionData['new_password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if ($isEmailChanging) {
                $sessionData['new_email'] = $data['admin_email'];
            }
            $_SESSION['otp_data'] = $sessionData;

            $mail = getMailer();
            if (!$mail) {
                throw new Exception("Mail server is not configured correctly.");
            }

            // Let's create a clear email explaining what's happening
            $subject = 'Your Admin Account Security Verification';
            $body = "Hello {$data['username']},<br><br>A request was made to update your account details. ";
            $body .= "Please use the following verification code to confirm the changes:<br><br><b>{$otp}</b><br><br>";
            if ($isEmailChanging) {
                $body .= "This change will set your new login email to: <b>{$data['admin_email']}</b>.<br>";
            }
            if ($isPasswordChanging) {
                $body .= "Your password will also be reset.<br>";
            }
            $body .= "<br>This code will expire in 5 minutes.<br><br>If you did not request this, you can safely ignore this email.<br><br>Thank you.";

            try {
                // Send the email to the OLD (currently verified) email address
                $mail->addAddress($currentEmail, $data['username']);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
                echo json_encode(["success" => true, "otp_required" => true]);
            } catch (Exception $e) {
                throw new Exception("Failed to send OTP email. Mailer Error: {$mail->ErrorInfo}");
            }
        }
    } else {
        // This handles updates without a password or email change (e.g., just username, role, profile pic).
        $query = "UPDATE login_admin SET username = ?, role = ?";
        $types = "ss";
        $params = [$data['username'], $data['role']];
        
        if ($adminPic !== null) {
            $query .= ", admin_pic = ?";
            $types .= "s";
            $params[] = $adminPic;
        }

        $query .= " WHERE admin_id = ?";
        $types .= "i";
        $params[] = $adminId;

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            throw new Exception("Database update failed: " . $stmt->error);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
