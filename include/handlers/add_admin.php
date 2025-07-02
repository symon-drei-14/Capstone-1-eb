<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password) || !isset($data->role) || 
    empty($data->username) || empty($data->password) || empty($data->role)) {
    echo json_encode(["success" => false, "message" => "Username, password and role are required"]);
    exit;
}

// Check if username already exists
$checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE username = ?");
$checkStmt->bind_param("s", $data->username);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already exists"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Add new admin
$stmt = $conn->prepare("INSERT INTO login_admin (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $data->username, $data->password, $data->role);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>