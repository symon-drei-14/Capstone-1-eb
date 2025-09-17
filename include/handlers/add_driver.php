<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

file_put_contents('add_driver_log.txt', 
    date('Y-m-d H:i:s') . " - Add Driver Request received\n", 
    FILE_APPEND);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

require_once 'dbhandler.php';

$host = "localhost";
$db_name = "capstonedb"; 
$username = "root";
$password = ""; 

try {
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
            throw new Exception("Invalid file type. Only JPG, PNG and GIF are allowed.");
        }
    }

    // Get the POST data (now from $_POST instead of JSON)
    $data = $_POST;
    
    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - POST data received successfully\n", 
        FILE_APPEND);

    // Validate required fields
    if (
        empty($data['name']) ||
        empty($data['email']) ||
        empty($data['contact_no']) ||
        empty($data['password']) ||
        !isset($data['assigned_truck_id'])
    ) {
        throw new Exception("Missing required fields");
    }

    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - Data validation passed\n", 
        FILE_APPEND);

    // Generate driver_id and firebase_uid (same as register.js)
    $driver_id = $data['driver_id'];
    $firebase_uid = $data['firebase_uid'];

    // Connect to MySQL
    $mysql_conn = new mysqli($host, $username, $password, $db_name);
    
    if ($mysql_conn->connect_error) {
        throw new Exception("MySQL Connection failed: " . $mysql_conn->connect_error);
    }

    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - MySQL connection successful\n", 
        FILE_APPEND);

    // Check if email already exists
    $check_email = "SELECT email FROM drivers_table WHERE email = ?";
    $stmt = $mysql_conn->prepare($check_email);
    
    if (!$stmt) {
        throw new Exception("Email check prepare failed: " . $mysql_conn->error);
    }
    
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email already exists");
    }
    $stmt->close();

    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - Email check passed\n", 
        FILE_APPEND);
        
    // --- BEGIN TRUCK ASSIGNMENT VALIDATION ---
    $assigned_truck_id = !empty($data['assigned_truck_id']) ? $data['assigned_truck_id'] : null;

    if ($assigned_truck_id !== null) {
        // Check if the truck is already assigned to another driver
        $check_truck = "SELECT driver_id FROM drivers_table WHERE assigned_truck_id = ?";
        $stmt_truck = $mysql_conn->prepare($check_truck);
        if (!$stmt_truck) {
            throw new Exception("Truck check prepare failed: " . $mysql_conn->error);
        }
        $stmt_truck->bind_param("s", $assigned_truck_id);
        $stmt_truck->execute();
        $result_truck = $stmt_truck->get_result();

        if ($result_truck->num_rows > 0) {
            throw new Exception("This truck is already assigned to another driver.");
        }
        $stmt_truck->close();
    }
    // --- END TRUCK ASSIGNMENT VALIDATION ---

    // Hash password for MySQL
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert into MySQL (now includes driver_pic)
    $query = "INSERT INTO drivers_table (driver_id, firebase_uid, name, email, contact_no, password, assigned_truck_id, driver_pic, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
              
    $stmt = $mysql_conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("MySQL Prepare statement failed: " . $mysql_conn->error);
    }

    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - MySQL SQL prepared successfully\n", 
        FILE_APPEND);
    
    $stmt->bind_param(
        "ssssssss",
        $driver_id,
        $firebase_uid,
        $data['name'],
        $data['email'],
        $data['contact_no'],
        $hashed_password,
        $assigned_truck_id, // Use the validated variable
        $driverPic
    );

    if (!$stmt->execute()) {
        throw new Exception("MySQL Execute failed: " . $stmt->error);
    }
    $stmt->close();
    
    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - MySQL insert successful\n", 
        FILE_APPEND);

    // Now save to Firebase
    $firebase_url = "https://mansartrucking1-default-rtdb.asia-southeast1.firebasedatabase.app/drivers/" . $driver_id . ".json?auth=Xtnh1Zva11o8FyDEA75gzep6NUeNJLMZiCK6mXB7";
    
    $firebase_data = array(
        "driver_id" => $driver_id,
        "name" => $data['name'],
        "email" => $data['email'],
        "password" => $data['password'], // Store plain password in Firebase as per your register.js logic
        "assigned_truck_id" => $assigned_truck_id ? intval($assigned_truck_id) : null,
        "created_at" => date('c'), // ISO 8601 format
        "last_login" => null,
        "location" => array(
            "latitude" => 0,
            "longitude" => 0,
            "last_updated" => null
        )
    );

    $firebase_json = json_encode($firebase_data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $firebase_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $firebase_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($firebase_json)
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $firebase_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Firebase cURL Error: " . $curl_error);
    }

    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("Firebase request failed with HTTP code: " . $http_code . " Response: " . $firebase_response);
    }

    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - Firebase save successful\n", 
        FILE_APPEND);

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Driver added successfully to both MySQL and Firebase",
        "driver_id" => $driver_id
    ]);

} catch (Exception $e) {
    file_put_contents('add_driver_log.txt', 
        date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", 
        FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
} finally {
    if (isset($mysql_conn)) $mysql_conn->close();
}
?>