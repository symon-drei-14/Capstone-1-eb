<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->admin_id)) {
    echo json_encode(["success" => false, "message" => "No admin ID provided"]);
    exit;
}

// Prevent deleting the last admin
$countResult = $conn->query("SELECT COUNT(*) as total FROM login_admin");
$totalRow = $countResult->fetch_assoc();
if ($totalRow['total'] <= 1) {
    echo json_encode(["success" => false, "message" => "Cannot delete the last admin"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM login_admin WHERE admin_id = ?");
$stmt->bind_param("i", $data->admin_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>