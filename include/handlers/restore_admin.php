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
$newEmail = $data['admin_email'] ?? null; // Added newEmail

// Fetch the current delete reason for specific logic
$reasonStmt = $conn->prepare("SELECT delete_reason FROM login_admin WHERE admin_id = ?");
$reasonStmt->bind_param("i", $adminId);
$reasonStmt->execute();
$reasonResult = $reasonStmt->get_result();
$adminData = $reasonResult->fetch_assoc();
$deleteReason = $adminData['delete_reason'] ?? '';
$reasonStmt->close();

// Define the reasons that require a mandatory password/email reset
$mandatoryResetReasons = ['Failed Login Attempts', 'Too many OTP attempts'];
$isMandatoryReset = in_array($deleteReason, $mandatoryResetReasons);

if ($isMandatoryReset) {
    // Mandate password and email change for security-locked accounts
    if (empty($newPassword) || empty($newEmail)) {
        echo json_encode(["success" => false, "message" => "Account locked for security reasons. New password and email are required for restoration."]);
        exit;
    }

    if (strlen($newPassword) < 8) {
        echo json_encode(["success" => false, "message" => "New password must be at least 8 characters long."]);
        exit;
    }
    
    // Simple email format validation
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format."]);
        exit;
    }
    
    // Check for duplicate email against other active admins
    $checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE admin_email = ? AND admin_id != ? AND is_deleted = FALSE");
    $checkStmt->bind_param("si", $newEmail, $adminId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        echo json_encode(["success" => false, "message" => "Email already exists for an active admin."]);
        exit;
    }
    $checkStmt->close();
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Reset all relevant fields, including new password and new email
    $stmt = $conn->prepare("UPDATE login_admin SET 
        password = ?,
        admin_email = ?,
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
    
    $stmt->bind_param("ssi", $hashedPassword, $newEmail, $adminId);

} else {
    // This is a standard restore (not security-locked, or restoring a non-security field)
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