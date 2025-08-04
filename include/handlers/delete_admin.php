<?php
header("Content-Type: application/json");
require 'dbhandler.php';
session_start();

error_log("=== Starting admin deletion ===");
error_log("Session data: " . print_r($_SESSION, true));

$data = json_decode(file_get_contents("php://input"));
error_log("Input data: " . print_r($data, true));

if (!isset($data->admin_id)) {
    error_log("Error: No admin ID provided");
    echo json_encode(["success" => false, "message" => "No admin ID provided"]);
    exit;
}

// Check if this is a full delete (permanent removal)
$is_full_delete = isset($data->full_delete) && $data->full_delete === true;

if ($is_full_delete) {
    // Perform hard delete
    $stmt = $conn->prepare("DELETE FROM login_admin WHERE admin_id = ? AND is_deleted = TRUE");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Database prepare error"]);
        exit;
    }
    
    $stmt->bind_param("i", $data->admin_id);
    if ($stmt->execute()) {
        error_log("Full delete successful - affected rows: " . $stmt->affected_rows);
        echo json_encode(["success" => true]);
    } else {
        $error = $stmt->error;
        error_log("Full delete failed: " . $error);
        echo json_encode(["success" => false, "message" => "Database error: " . $error]);
    }
    $stmt->close();
} else {
    // Original soft delete logic
    $deleted_by = $_SESSION['username'] ?? null;
    $reason = $data->delete_reason ?? 'No reason provided';

    error_log("Attempting to delete admin {$data->admin_id}");
    error_log("Deleted by: {$deleted_by}");
    error_log("Reason: {$reason}");

    if (!$deleted_by) {
        error_log("Error: Not authenticated - username not in session");
        echo json_encode(["success" => false, "message" => "Not authenticated"]);
        exit;
    }

    // Count check only for soft deletes
    $countResult = $conn->query("SELECT COUNT(*) as total FROM login_admin WHERE is_deleted = FALSE");
    $totalRow = $countResult->fetch_assoc();
    if ($totalRow['total'] <= 1) {
        error_log("Error: Attempt to delete last admin");
        echo json_encode(["success" => false, "message" => "Cannot delete the last active admin"]);
        exit;
    }

    // Perform soft delete
    $stmt = $conn->prepare("UPDATE login_admin SET 
        is_deleted = TRUE, 
        deleted_at = NOW(), 
        deleted_by = ?, 
        delete_reason = ?
        WHERE admin_id = ?");

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Database prepare error"]);
        exit;
    }

    $stmt->bind_param("ssi", $deleted_by, $reason, $data->admin_id);  
    if ($stmt->execute()) {
        error_log("Delete successful - affected rows: " . $stmt->affected_rows);
        
        // Verify the update
        $check = $conn->query("SELECT deleted_by, delete_reason FROM login_admin WHERE admin_id = {$data->admin_id}");
        if ($check) {
            error_log("Verification: " . print_r($check->fetch_assoc(), true));
        }
        
        echo json_encode(["success" => true]);
    } else {
        $error = $stmt->error;
        error_log("Delete failed: " . $error);
        echo json_encode(["success" => false, "message" => "Database error: " . $error]);
    }
    $stmt->close();
}

$conn->close();
?>