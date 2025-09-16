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

// Validate required fields - either profile update or password update
if (!isset($data['driver_id']) || (!isset($data['name']) && !isset($data['email']) && !isset($data['contact_no']) && !isset($data['password']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: driver_id and at least one field to update (name, email, contact_no, or password)'
    ]);
    exit;
}

$driver_id = $data['driver_id'];
$name = isset($data['name']) ? trim($data['name']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$contact_no = isset($data['contact_no']) ? trim($data['contact_no']) : null;
$password = isset($data['password']) ? trim($data['password']) : null;

// Validate email format if provided
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

try {
    // Check if email already exists for another driver (if email is being updated)
    if ($email) {
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
    }

    // Build the update query dynamically
    $updateFields = [];
    $params = [];
    $types = "";

    if ($name) {
        $updateFields[] = "name = ?";
        $params[] = $name;
        $types .= "s";
    }

    if ($email) {
        $updateFields[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }

    if ($contact_no) {
        $updateFields[] = "contact_no = ?";
        $params[] = $contact_no;
        $types .= "s";
    }

    if ($password) {
        // Hash the password using the same method as in add_driver.php and save_driver.php
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    if (empty($updateFields)) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid fields to update'
        ]);
        exit;
    }

    // Add driver_id to parameters
    $params[] = $driver_id;
    $types .= "s";

    // Update driver information
    $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
   
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