<?php
header("Content-Type: application/json");
require 'dbhandler.php';

session_start();

// Check if user is logged in and has appropriate permissions if necessary
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->admin_id) || !isset($data->username) || empty($data->username) || !isset($data->role) || empty($data->role)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Admin ID, username, and role are required"]);
    exit;
}

try {
    // Check if username already exists for another admin
    $checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE username = ? AND admin_id != ?");
    $checkStmt->bind_param("si", $data->username, $data->admin_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        throw new Exception("Username already exists");
    }
    $checkStmt->close();

    // Initialize query parts
    $query = "UPDATE login_admin SET username = ?, role = ?";
    $types = "ss";
    $params = [$data->username, $data->role];

    // If a new password is provided, validate the old one and add it to the query
    if (!empty($data->password)) {
        if (empty($data->old_password)) {
            throw new Exception("Current password is required to set a new one.");
        }

        // Fetch the current password hash from the database
        $passStmt = $conn->prepare("SELECT password FROM login_admin WHERE admin_id = ?");
        $passStmt->bind_param("i", $data->admin_id);
        $passStmt->execute();
        $passResult = $passStmt->get_result();

        if ($passResult->num_rows === 0) {
            throw new Exception("Admin not found.");
        }
        
        $currentHash = $passResult->fetch_assoc()['password'];
        $passStmt->close();
        
        // Verify the provided old password against the stored hash
        if (!password_verify($data->old_password, $currentHash)) {
            throw new Exception("Incorrect current password.");
        }

        // If validation passes, add the new hashed password to the query
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
        $query .= ", password = ?";
        $types .= "s";
        $params[] = $hashedPassword;
    }

    // Finalize the query
    $query .= " WHERE admin_id = ?";
    $types .= "i";
    $params[] = $data->admin_id;

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        throw new Exception("Database update failed: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>