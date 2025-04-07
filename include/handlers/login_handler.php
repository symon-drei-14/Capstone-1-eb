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

    file_put_contents('login_log.txt', 
        date('Y-m-d H:i:s') . " - JSON parsed successfully\n", 
        FILE_APPEND);

    if (empty($data->email) || empty($data->password)) {
        throw new Exception("Email and password are required");
    }

    file_put_contents('login_log.txt', 
        date('Y-m-d H:i:s') . " - Data validation passed\n", 
        FILE_APPEND);

    $conn = new mysqli($host, $username, $password, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    file_put_contents('login_log.txt', 
        date('Y-m-d H:i:s') . " - Database connection successful\n", 
        FILE_APPEND);

    $query = "SELECT driver_id, firebase_uid, name, email, password, assigned_truck_id FROM drivers_table WHERE email = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        file_put_contents('login_log.txt', 
            date('Y-m-d H:i:s') . " - User not found: {$data->email}\n", 
            FILE_APPEND);
        
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "invalid_email",
            "message" => "User not found"
        ]);
        exit;
    }

    $user = $result->fetch_assoc();
    
    if (password_verify($data->password, $user['password'])) {
        file_put_contents('login_log.txt', 
            date('Y-m-d H:i:s') . " - Login successful for: {$data->email}\n", 
            FILE_APPEND);

        $update_query = "UPDATE drivers_table SET last_login = NOW() WHERE driver_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("s", $user['driver_id']);
        $update_stmt->execute();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "driver_id" => $user['driver_id'],
                "firebase_uid" => $user['firebase_uid'],
                "name" => $user['name'],
                "email" => $user['email'],
                "assigned_truck_id" => $user['assigned_truck_id']
            ]
        ]);
    } else {
        file_put_contents('login_log.txt', 
            date('Y-m-d H:i:s') . " - Incorrect password for: {$data->email}\n", 
            FILE_APPEND);
        
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "invalid_password",
            "message" => "Incorrect password"
        ]);
    }
} catch (Exception $e) {
    file_put_contents('login_log.txt', 
        date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", 
        FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>