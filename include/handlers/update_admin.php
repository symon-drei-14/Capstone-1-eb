<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->admin_id) || !isset($data->username) || empty($data->username)) {
    echo json_encode(["success" => false, "message" => "Admin ID and username are required"]);
    exit;
}

// Check if username already exists for another admin
$checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE username = ? AND admin_id != ?");
$checkStmt->bind_param("si", $data->username, $data->admin_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already exists"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Update admin
if (!empty($data->password)) {
    // Update username and password
    $stmt = $conn->prepare("UPDATE login_admin SET username = ?, password = ? WHERE admin_id = ?");
    $stmt->bind_param("ssi", $data->username, $data->password, $data->admin_id);
} else {
    // Update username only
    $stmt = $conn->prepare("UPDATE login_admin SET username = ? WHERE admin_id = ?");
    $stmt->bind_param("si", $data->username, $data->admin_id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>