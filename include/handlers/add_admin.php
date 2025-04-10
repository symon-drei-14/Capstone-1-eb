<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password) || empty($data->username) || empty($data->password)) {
    echo json_encode(["success" => false, "message" => "Username and password are required"]);
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
$stmt = $conn->prepare("INSERT INTO login_admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $data->username, $data->password);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>