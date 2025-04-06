<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if all required fields are present
if (!isset($data['id']) ||
    !isset($data['plateNumber']) || 
    !isset($data['driver']) || 
    !isset($data['helper']) || 
    !isset($data['containerNo']) || 
    !isset($data['client']) || 
    !isset($data['shippingLine']) || 
    !isset($data['consignee']) || 
    !isset($data['size']) || 
    !isset($data['cashAdvance']) || 
    !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Update the trip in the database
$sql = "UPDATE assign_trip2 SET 
        plateNumber = ?, 
        driver = ?, 
        helper = ?, 
        containerNo = ?, 
        client = ?, 
        shippingLine = ?, 
        consignee = ?, 
        size = ?, 
        cashAdvance = ?, 
        status = ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssssi", 
    $data['plateNumber'],
    $data['driver'],
    $data['helper'],
    $data['containerNo'],
    $data['client'],
    $data['shippingLine'],
    $data['consignee'],
    $data['size'],
    $data['cashAdvance'],
    $data['status'],
    $data['id']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>