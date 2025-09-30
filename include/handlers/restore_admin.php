<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['admin_id'])) {
    echo json_encode(["success" => false, "message" => "No admin ID provided"]);
    exit;
}

$adminId = $data['admin_id'];
$newPassword = $data['password'] ?? null;

if ($newPassword) {
    // This is a restore with a mandatory password reset
    if (empty($newPassword)) {
        echo json_encode(["success" => false, "message" => "New password cannot be empty."]);
        exit;
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Reset all relevant fields
    $stmt = $conn->prepare("UPDATE login_admin SET 
        password = ?,
        is_deleted = FALSE, 
        deleted_at = NULL, 
        deleted_by = NULL, 
        delete_reason = NULL,
        failed_attempts = 0,
        last_failed_attempt = NULL
        WHERE admin_id = ?");
    
    if (!$stmt) {
         echo json_encode(["success" => false, "message" => "Database prepare error: " . $conn->error]);
         exit;
    }
    
    $stmt->bind_param("si", $hashedPassword, $adminId);

} else {
    // This is a standard restore for an admin not locked by failed attempts
    $stmt = $conn->prepare("UPDATE login_admin SET 
        is_deleted = FALSE, 
        deleted_at = NULL, 
        deleted_by = NULL, 
        delete_reason = NULL 
        WHERE admin_id = ?");

    if (!$stmt) {
         echo json_encode(["success" => false, "message" => "Database prepare error: " . $conn->error]);
         exit;
    }
        
    $stmt->bind_param("i", $adminId);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database update error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>