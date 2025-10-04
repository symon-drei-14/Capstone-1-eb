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
    // Check if username or email already exists for another admin
    $checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE (username = ? OR admin_email = ?) AND admin_id != ?");
    $checkStmt->bind_param("ssi", $data['username'], $data['admin_email'], $adminId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception("Username or email already taken by another admin.");
    }
    $checkStmt->close();

    // Check if a password change is requested
    if (!empty($data['password'])) {
        
        // Step 1: Verify the current password
        if (empty($data['old_password'])) {
            throw new Exception("Current password is required to set a new one.");
        }
        $passStmt = $conn->prepare("SELECT password FROM login_admin WHERE admin_id = ?");
        $passStmt->bind_param("i", $adminId);
        $passStmt->execute();
        $passResult = $passStmt->get_result();
        $currentHash = $passResult->fetch_assoc()['password'];
        $passStmt->close();
        if (!password_verify($data['old_password'], $currentHash)) {
            throw new Exception("Incorrect current password.");
        }

        // Step 2: Handle OTP verification
        if (isset($data['otp'])) {
            // This is the second step: verifying the submitted OTP
            if (!isset($_SESSION['otp_data']) || $_SESSION['otp_data']['admin_id'] != $adminId) {
                throw new Exception("OTP process not initiated or session expired.");
            }
            if ($_SESSION['otp_data']['otp'] != $data['otp']) {
                throw new Exception("The OTP you entered is incorrect.");
            }
            if (time() > $_SESSION['otp_data']['expiry']) {
                unset($_SESSION['otp_data']);
                throw new Exception("OTP has expired. Please try changing your password again.");
            }

            // OTP is valid, so we update everything including the password
            $hashedPassword = $_SESSION['otp_data']['new_password'];
            unset($_SESSION['otp_data']);

            $query = "UPDATE login_admin SET username = ?, role = ?, admin_email = ?, password = ?";
            $types = "ssss";
            $params = [$data['username'], $data['role'], $data['admin_email'], $hashedPassword];

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
            // This is the first step: generating and sending the OTP
            $otp = rand(100000, 999999);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $_SESSION['otp_data'] = [
                'otp' => $otp,
                'expiry' => time() + 300, // OTP is valid for 5 minutes
                'admin_id' => $adminId,
                'new_password' => $hashedPassword
            ];

            $mail = getMailer();
            if ($mail) {
                try {
                    $mail->addAddress($data['admin_email'], $data['username']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Password Change Verification Code';
                    $mail->Body    = "Hello {$data['username']},<br><br>Your verification code to change your password is: <b>{$otp}</b><br>This code will expire in 5 minutes.<br><br>If you did not request this change, you can safely ignore this email.<br><br>Thank you.";
                    $mail->send();
                    echo json_encode(["success" => true, "otp_required" => true]);
                } catch (Exception $e) {
                    throw new Exception("Failed to send OTP email. Mailer Error: {$mail->ErrorInfo}");
                }
            } else {
                throw new Exception("Mail server is not configured correctly.");
            }
        }
    } else {
        // This handles updates without a password change
        $query = "UPDATE login_admin SET username = ?, role = ?, admin_email = ?";
        $types = "sss";
        $params = [$data['username'], $data['role'], $data['admin_email']];
        
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
