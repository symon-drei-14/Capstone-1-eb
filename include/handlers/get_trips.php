<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$result = $conn->query("SELECT * FROM assign_trip2");

$trips = [];
while ($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

echo json_encode($trips);

$conn->close();
?>