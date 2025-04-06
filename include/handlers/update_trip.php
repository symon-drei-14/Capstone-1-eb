<?php
require 'dbhandler.inc.php';
// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Get POST data
$trip_id = $_POST['trip_id'];
$plate_num = $_POST['plate_num'];
$driver = $_POST['driver'];
$helper = $_POST['helper'];
$container_no = $_POST['container_no'];
$client = $_POST['client'];
$shipping_line = $_POST['shipping_line'];
$consignee = $_POST['consignee'];
$size = $_POST['size'];
$cash_advance = $_POST['cash_advance'];
$status = $_POST['status'];

// Remove currency symbol and commas from cash_advance if present
$cash_advance = preg_replace('/[^0-9.]/', '', $cash_advance);

// Prepare SQL statement
$sql = "UPDATE assign_trip2 SET 
        Plate_num = ?, 
        Driver = ?, 
        Helper = ?, 
        Container_no = ?, 
        Client = ?, 
        Shipping_line = ?, 
        Consignee = ?, 
        Size = ?, 
        Cash_advance = ?, 
        Status = ? 
        WHERE Trip_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode([
        'success' => false,
        'message' => "Prepare failed: " . $conn->error
    ]));
}

// Bind parameters
$stmt->bind_param("sssissssssi", 
    $plate_num, 
    $driver, 
    $helper, 
    $container_no, 
    $client, 
    $shipping_line, 
    $consignee, 
    $size, 
    $cash_advance, 
    $status,
    $trip_id
);

// Execute statement
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => "Trip updated successfully"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "Error: " . $stmt->error
    ]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>