<?php
// dashboard_handler.php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

try {
    // Get all ongoing trips (status = "En Route")
    $stmt = $conn->prepare("SELECT * FROM assign WHERE status = 'En Route'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ongoingTrips = [];
    while ($row = $result->fetch_assoc()) {
        $ongoingTrips[] = [
            'plate_no' => $row['plate_no'],
            'driver' => $row['driver'],
            'helper' => $row['helper'],
            'client' => $row['client'],
            'destination' => $row['destination']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $ongoingTrips]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>