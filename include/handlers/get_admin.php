<?php
// single admin
header("Content-Type: application/json");
require 'dbhandler.php';

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "No admin ID provided"]);
    exit;
}

$adminId = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT admin_id, username, role, admin_pic FROM login_admin WHERE admin_id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo json_encode(["success" => true, "admin" => $admin]);
} else {
    echo json_encode(["success" => false, "message" => "Admin not found"]);
}

$stmt->close();
$conn->close();
?>