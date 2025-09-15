<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once 'dbhandler.php';

// Check database connection first
if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]); 
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['driver_id']) || !isset($data['name']) || !isset($data['email']) || !isset($data['contact_no'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: driver_id, name, email, and contact_no are required'
    ]);
    exit;
}

$driver_id = $data['driver_id'];
$name = trim($data['name']);
$email = trim($data['email']);
$contact_no = trim($data['contact_no']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

try {
    // Check if email already exists for another driver
    $check_email = $conn->prepare("SELECT driver_id FROM drivers_table WHERE email = ? AND driver_id != ?");
    $check_email->bind_param("ss", $email, $driver_id);
    $check_email->execute();
    $email_result = $check_email->get_result();
    
    if ($email_result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists for another driver'
        ]);
        exit;
    }
    $check_email->close();

    // Update driver information
    $stmt = $conn->prepare("UPDATE drivers_table SET name = ?, email = ?, contact_no = ? WHERE driver_id = ?");
    $stmt->bind_param("ssss", $name, $email, $contact_no, $driver_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Driver information updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No changes were made to the driver information'
            ]);
        }
    } else {
        throw new Exception("Failed to update driver: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Update mobile driver error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating driver information: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>