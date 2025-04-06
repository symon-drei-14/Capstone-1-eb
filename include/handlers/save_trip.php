<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->plateNumber, $data->driver, $data->helper, $data->containerNo)) {
    echo json_encode(["success" => false, "message" => "Incomplete data"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO assign_trip2 (plate_num, driver, helper, container_no, client, shipping_line, consignee, size, cash_advance, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", 
    $data->plateNumber, 
    $data->driver, 
    $data->helper, 
    $data->containerNo, 
    $data->client, 
    $data->shippingLine, 
    $data->consignee, 
    $data->size, 
    $data->cashAdvance, 
    $data->status
);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

$stmt->close();
$conn->close();
?>