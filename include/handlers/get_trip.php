<?php
header("Content-Type: application/json");
require 'dbhandler.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT 
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
    FROM assign_trip2 WHERE Trip_id = ?");
    
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'trip' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Trip not found']);
}

$stmt->close();
$conn->close();
?>