<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$result = $conn->query("SELECT 
    Trip_id as id,
    Plate_num as plateNumber,
    Driver as driver,
    Helper as helper,
    Container_no as containerNo,
    Client as client,
    Shipping_line as shippingLine,
    Consignee as consignee,
    Size as size,
    Cash_advance as cashAdvance,
    Status as status
    FROM assign_trip2");

if ($result) {
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    echo json_encode(['success' => true, 'trips' => $trips]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$conn->close();
?>