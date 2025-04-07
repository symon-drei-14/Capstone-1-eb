<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

file_put_contents('api_log.txt', 
    date('Y-m-d H:i:s') . " - Request received\n" . 
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

    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - JSON parsed successfully\n", 
        FILE_APPEND);

    if (
        empty($data->driver_id) ||
        empty($data->firebase_uid) ||
        empty($data->name) ||
        empty($data->email) ||
        empty($data->password) ||
        !isset($data->assigned_truck_id)
    ) {
        throw new Exception("Missing required fields");
    }

    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - Data validation passed\n", 
        FILE_APPEND);

    $conn = new mysqli($host, $username, $password, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - Database connection successful\n", 
        FILE_APPEND);

    $table_check = $conn->query("SHOW TABLES LIKE 'drivers_table'");
    if ($table_check->num_rows == 0) {
        file_put_contents('api_log.txt', 
            date('Y-m-d H:i:s') . " - Table 'drivers_table' does not exist, creating it\n", 
            FILE_APPEND);
            
        $create_table = "CREATE TABLE drivers_table (
            driver_id VARCHAR(50) NOT NULL PRIMARY KEY,
            firebase_uid VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            assigned_truck_id INT(11) NULL,
            created_at DATETIME NOT NULL,
            last_login DATETIME NULL
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Table creation failed: " . $conn->error);
        }
        
        file_put_contents('api_log.txt', 
            date('Y-m-d H:i:s') . " - Table created successfully\n", 
            FILE_APPEND);
    } else {
        file_put_contents('api_log.txt', 
            date('Y-m-d H:i:s') . " - Table 'drivers_table' exists\n", 
            FILE_APPEND);
    }

    $table_structure = $conn->query("DESCRIBE drivers_table");
    $columns = [];
    while ($row = $table_structure->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - Table columns: " . implode(", ", $columns) . "\n", 
        FILE_APPEND);

    $check_email = "SELECT email FROM drivers_table WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    
    if (!$stmt) {
        throw new Exception("Email check prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email already exists");
    }

    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - Email check passed\n", 
        FILE_APPEND);

    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

    $query = "INSERT INTO drivers_table (driver_id, firebase_uid, name, email, password, assigned_truck_id, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
              
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - SQL prepared successfully\n", 
        FILE_APPEND);
    
    $stmt->bind_param(
        "sssssi",
        $data->driver_id,
        $data->firebase_uid,
        $data->name,
        $data->email,
        $hashed_password,
        $data->assigned_truck_id
    );

    if ($stmt->execute()) {
        file_put_contents('api_log.txt', 
            date('Y-m-d H:i:s') . " - Driver registered successfully\n", 
            FILE_APPEND);
            
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Driver registered successfully",
            "driver_id" => $data->driver_id
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
} catch (Exception $e) {

    file_put_contents('api_log.txt', 
        date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", 
        FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>