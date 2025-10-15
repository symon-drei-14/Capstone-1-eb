<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

file_put_contents('login_log.txt', 
    date('Y-m-d H:i:s') . " - Login request received\n" . 
    file_get_contents("php://input") . "\n\n", 
    FILE_APPEND);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();
// --- FIX: Ensure correct timezone is set for accurate lockout calculations ---
date_default_timezone_set('Asia/Manila');
require_once 'dbhandler.php';
require_once 'phpmailer_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}


try {
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        throw new Exception("Invalid JSON input: " . $input);
    }

    $conn = new mysqli($host, $username, $password, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Define max attempts and lockout duration (you can adjust these)
    $MAX_LOGIN_ATTEMPTS = 5;
    $LOGIN_COOLDOWN_SECONDS = 300; // 5 minutes

    // Step 2: OTP Verification
    if (!empty($data->otp) && !empty($data->email)) {
        
        // Fetch current driver data to get otp_attempts
        $query_driver = "SELECT driver_id, otp_attempts FROM drivers_table WHERE email = ?";
        $stmt_driver = $conn->prepare($query_driver);
        $stmt_driver->bind_param("s", $data->email);
        $stmt_driver->execute();
        $result_driver = $stmt_driver->get_result();
        $driver = $result_driver->fetch_assoc();
        $stmt_driver->close();

        if (!$driver) {
            throw new Exception("User not found or email mismatch.");
        }

        $current_otp_attempts = (int)$driver['otp_attempts'];
        $MAX_OTP_ATTEMPTS = 3;

        // Check if attempts exceeded before even verifying OTP
        if ($current_otp_attempts >= $MAX_OTP_ATTEMPTS) {
            http_response_code(403);
            echo json_encode(["success" => false, "error" => "otp_blocked", "message" => "You have exceeded the maximum OTP attempts. Please request a new login to get a new code."]);
            exit;
        }

        if (!isset($_SESSION['login_otp']) || $_SESSION['login_otp']['email'] !== $data->email) {
            throw new Exception("OTP process not started or session expired. Please log in again.");
        }
        
        if (time() > $_SESSION['login_otp']['expiry']) {
            // OTP expired. Reset driver's OTP attempts since they must start a new process.
            $reset_otp_attempts = "UPDATE drivers_table SET otp_attempts = 0 WHERE driver_id = ?";
            $reset_stmt = $conn->prepare($reset_otp_attempts);
            $reset_stmt->bind_param("s", $driver['driver_id']);
            $reset_stmt->execute();
            $reset_stmt->close();

            unset($_SESSION['login_otp']);
            throw new Exception("OTP has expired. Please try logging in again.");
        }

        if ($_SESSION['login_otp']['otp'] != $data->otp) {
            // Invalid OTP, increment attempt count in DB
            $new_otp_attempts = $current_otp_attempts + 1;
            $update_attempts_query = "UPDATE drivers_table SET otp_attempts = ? WHERE driver_id = ?";
            $update_attempts_stmt = $conn->prepare($update_attempts_query);
            $update_attempts_stmt->bind_param("is", $new_otp_attempts, $driver['driver_id']);
            $update_attempts_stmt->execute();
            $update_attempts_stmt->close();
            
            $remaining = $MAX_OTP_ATTEMPTS - $new_otp_attempts;
            
            // If they are now blocked after this attempt
            if ($new_otp_attempts >= $MAX_OTP_ATTEMPTS) {
                 // Get user details from session before unsetting it
                 $user_data_for_email = $_SESSION['login_otp']['user'];
                 $user_name = $user_data_for_email['name'] ?? $data->email;

                 unset($_SESSION['login_otp']); // Invalidate session OTP
                 
                 // --- EMAIL NOTIFICATION FOR OTP BLOCKOUT ---
                 $mail = getMailer();
                 if ($mail) {
                     try {
                         $mail->addAddress($data->email, $user_name);
                         $mail->isHTML(true);
                         $mail->Subject = 'Security Alert: OTP Attempts Exceeded';
                         $mail->Body    = "Hello {$user_name},<br><br>This is a security notification. The One-Time Password (OTP) for your login session has been **invalidated** because the maximum number of verification attempts (**{$MAX_OTP_ATTEMPTS}**) was exceeded.<br><br>To proceed, please **start a new login attempt** using your email and password.<br><br>If you did not perform these actions, please contact the IT department immediately.<br><br>Thank you.";
                         $mail->send();
                     } catch (Exception $e) {
                         error_log("Failed to send OTP block email to {$data->email}: " . $e->getMessage());
                     }
                 }
                 // --- END NEW EMAIL NOTIFICATION ---

                 http_response_code(403);
                 echo json_encode(["success" => false, "error" => "otp_blocked", "message" => "Too many failed OTP attempts. The code is now invalid. Please log in again to request a new code."]);
                 exit;
            }

            http_response_code(401);
            throw new Exception("The OTP you entered is incorrect. You have {$remaining} attempts remaining.");
        }

        // OTP is correct, finalize login and reset counters
        $user_data = $_SESSION['login_otp']['user'];
        unset($_SESSION['login_otp']);

        $update_query = "UPDATE drivers_table SET last_login = NOW(), login_attempts = 0, login_cooldown_until = NULL, otp_attempts = 0 WHERE driver_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("s", $user_data['driver_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => $user_data
        ]);

    // Step 1: Password Verification
    } else if (!empty($data->email) && !empty($data->password)) {
        // Fetch all necessary fields including the lockout fields
        $query = "SELECT driver_id, firebase_uid, name, email, password, assigned_truck_id, login_attempts, login_cooldown_until FROM drivers_table WHERE email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) throw new Exception("Prepare statement failed: " . $conn->error);
        
        $stmt->bind_param("s", $data->email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(401);
            echo json_encode(["success" => false, "error" => "invalid_email", "message" => "User not found"]);
            exit;
        }

        $user = $result->fetch_assoc();
        $now = time();
        $cooldown_until_time = strtotime($user['login_cooldown_until'] ?? '1970-01-01 00:00:00');
        
        // --- CHECK COOLDOWN TIMER ---
        if (!empty($user['login_cooldown_until']) && $now < $cooldown_until_time) {
            $timeLeft = ceil(($cooldown_until_time - $now) / 60);
            http_response_code(403);
            echo json_encode(["success" => false, "error" => "login_locked", "message" => "Too many failed attempts. Account locked. Please try again in {$timeLeft} minute(s)."]);
            $stmt->close(); exit;
        }

        // --- PASSWORD VERIFICATION ---
        if (password_verify($data->password, $user['password'])) {
            // Reset attempts and cooldown on success (will be finalized after OTP)
            $reset_attempts_query = "UPDATE drivers_table SET login_attempts = 0, login_cooldown_until = NULL, otp_attempts = 0 WHERE driver_id = ?";
            $reset_stmt = $conn->prepare($reset_attempts_query);
            $reset_stmt->bind_param("s", $user['driver_id']);
            $reset_stmt->execute();
            $reset_stmt->close();
            
            // --- START OTP FLOW ---
            $otp = rand(100000, 999999);
            $_SESSION['login_otp'] = [
                'otp' => $otp,
                'expiry' => time() + 300, // 5 minutes validity
                'email' => $user['email'],
                'user' => [
                    "driver_id" => $user['driver_id'],
                    "firebase_uid" => $user['firebase_uid'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "assigned_truck_id" => $user['assigned_truck_id']
                ]
            ];

            // Reset OTP attempts in DB when new OTP is generated
            $reset_otp_attempts = "UPDATE drivers_table SET otp_attempts = 0 WHERE driver_id = ?";
            $reset_otp_stmt = $conn->prepare($reset_otp_attempts);
            $reset_otp_stmt->bind_param("s", $user['driver_id']);
            $reset_otp_stmt->execute();
            $reset_otp_stmt->close();


            $mail = getMailer();
            if (!$mail) throw new Exception("Mail server is not configured.");

            $mail->addAddress($user['email'], $user['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Your Login Verification Code';
            $mail->Body    = "Hello {$user['name']},<br><br>Your verification code for Mansar Logistics is: <b>{$otp}</b><br><br>This code will expire in 5 minutes.<br><br>If you did not request this, please ignore this email.";
            $mail->send();

            echo json_encode(["success" => true, "otp_required" => true, "message" => "A verification code has been sent to your email."]);
        } else {
            // --- FAILED PASSWORD ---
            $new_attempts = (int)$user['login_attempts'] + 1;
            $new_cooldown_until = 'NULL';
            $error_message = "Incorrect password. You have {$user['login_attempts']} failed attempts.";

            if ($new_attempts >= $MAX_LOGIN_ATTEMPTS) {
                // Lock the account temporarily
                $lock_time = date('Y-m-d H:i:s', $now + $LOGIN_COOLDOWN_SECONDS);
                $new_cooldown_until = "'$lock_time'";
                $new_attempts = 0; // Reset attempts after lockout is applied
                $error_message = "Incorrect password. Account locked for " . ($LOGIN_COOLDOWN_SECONDS / 60) . " minutes.";

                // --- EMAIL NOTIFICATION FOR LOGIN LOCKOUT ---
                $mail = getMailer();
                if ($mail) {
                    try {
                        $mail->addAddress($user['email'], $user['name']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Security Alert: Account Temporarily Locked';
                        $mail->Body    = "Hello {$user['name']},<br><br>Your driver account has been temporarily locked due to **too many failed login attempts**.<br><br>The account will automatically unlock at: **" . date('Y-m-d H:i:s', $now + $LOGIN_COOLDOWN_SECONDS) . " (Asia/Manila)**.<br><br>If you did not attempt to log in, please ensure your credentials are secure and contact the IT department immediately.<br><br>Thank you.";
                        $mail->send();
                    } catch (Exception $e) {
                        // Log mail error, but continue process
                        error_log("Failed to send login lockout email to {$user['email']}: " . $e->getMessage());
                    }
                }
                // --- END NEW EMAIL NOTIFICATION ---

            } else {
                 $error_message = "Incorrect password. You have " . ($MAX_LOGIN_ATTEMPTS - $new_attempts) . " tries left before lockout.";
            }

            // Update attempts and cooldown status
            $update_fail_query = "UPDATE drivers_table SET login_attempts = ?, login_cooldown_until = $new_cooldown_until WHERE driver_id = ?";
            $update_fail_stmt = $conn->prepare($update_fail_query);
            // $new_attempts is 0 if locked, otherwise it's $user['login_attempts'] + 1
            $attempts_to_store = ($new_cooldown_until !== 'NULL') ? 0 : $new_attempts;
            $update_fail_stmt->bind_param("is", $new_attempts, $user['driver_id']);
            
            // Note: The logic here is tricky due to $new_cooldown_until being a string 'NULL' or a quoted datetime.
            // Using two separate queries is safer and clearer for mixed NULL/Value updates.
            $update_fail_query_1 = "UPDATE drivers_table SET login_attempts = ? WHERE driver_id = ?";
            $update_fail_stmt_1 = $conn->prepare($update_fail_query_1);
            $update_fail_stmt_1->bind_param("is", $new_attempts, $user['driver_id']);
            $update_fail_stmt_1->execute();
            $update_fail_stmt_1->close();
            
            if ($new_cooldown_until !== 'NULL') {
                $update_fail_query_2 = "UPDATE drivers_table SET login_cooldown_until = ? WHERE driver_id = ?";
                $update_fail_stmt_2 = $conn->prepare($update_fail_query_2);
                $update_fail_stmt_2->bind_param("ss", $lock_time, $user['driver_id']);
                $update_fail_stmt_2->execute();
                $update_fail_stmt_2->close();
            } else {
                 $update_fail_query_2 = "UPDATE drivers_table SET login_cooldown_until = NULL WHERE driver_id = ?";
                 $update_fail_stmt_2 = $conn->prepare($update_fail_query_2);
                 $update_fail_stmt_2->bind_param("s", $user['driver_id']);
                 $update_fail_stmt_2->execute();
                 $update_fail_stmt_2->close();
            }

            http_response_code(401);
            echo json_encode(["success" => false, "error" => "invalid_password", "message" => $error_message]);
        }
        $stmt->close();
    } else {
        throw new Exception("Email and password are required");
    }

} catch (Exception $e) {
    file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
