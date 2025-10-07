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

$host = "localhost";
$db_name = "capstonedb"; 
$username = "root"; 
$password = ""; 

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
    
    // Step 2: OTP Verification
    if (!empty($data->otp) && !empty($data->email)) {
        if (!isset($_SESSION['login_otp']) || $_SESSION['login_otp']['email'] !== $data->email) {
            throw new Exception("OTP process not started or session expired. Please log in again.");
        }
        if ($_SESSION['login_otp']['otp'] != $data->otp) {
            throw new Exception("The OTP you entered is incorrect.");
        }
        if (time() > $_SESSION['login_otp']['expiry']) {
            unset($_SESSION['login_otp']);
            throw new Exception("OTP has expired. Please try logging in again.");
        }

        $user_data = $_SESSION['login_otp']['user'];
        unset($_SESSION['login_otp']);

        $update_query = "UPDATE drivers_table SET last_login = NOW() WHERE driver_id = ?";
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
        $query = "SELECT driver_id, firebase_uid, name, email, password, assigned_truck_id FROM drivers_table WHERE email = ?";
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
        
        if (password_verify($data->password, $user['password'])) {
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

            $mail = getMailer();
            if (!$mail) throw new Exception("Mail server is not configured.");

            $mail->addAddress($user['email'], $user['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Your Login Verification Code';
            $mail->Body    = "Hello {$user['name']},<br><br>Your verification code for Mansar Logistics is: <b>{$otp}</b><br><br>This code will expire in 5 minutes.<br><br>If you did not request this, please ignore this email.";
            $mail->send();

            echo json_encode(["success" => true, "otp_required" => true, "message" => "A verification code has been sent to your email."]);
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "error" => "invalid_password", "message" => "Incorrect password"]);
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