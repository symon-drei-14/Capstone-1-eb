<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->admin_id)) {
    echo json_encode(["success" => false, "message" => "No admin ID provided"]);
    exit;
}

$stmt = $conn->prepare("UPDATE login_admin SET 
    is_deleted = FALSE, 
    deleted_at = NULL, 
    deleted_by = NULL, 
    delete_reason = NULL
    WHERE admin_id = ?");

$stmt->bind_param("i", $data->admin_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>