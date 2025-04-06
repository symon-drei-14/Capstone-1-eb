<?php
require 'dbhandler.inc.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Get trip ID from POST
$trip_id = $_POST['trip_id'];

// Prepare SQL statement
$sql = "DELETE FROM assign_trip2 WHERE Trip_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode([
        'success' => false,
        'message' => "Prepare failed: " . $conn->error
    ]));
}

// Bind parameter
$stmt->bind_param("i", $trip_id);

// Execute statement
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => "Trip deleted successfully"
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