<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if ID is present
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

// Delete the trip from the database
$sql = "DELETE FROM assign_trip2 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $data['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>