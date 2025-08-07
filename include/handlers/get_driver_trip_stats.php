<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Check if driver ID is provided
if (!isset($_GET['driver_id'])) {
    echo json_encode(["success" => false, "message" => "Driver ID is required"]);
    exit;
}

$driverId = $_GET['driver_id'];

try {
    // Get driver name first
    $getDriverName = $conn->prepare("SELECT name FROM drivers_table WHERE driver_id = ?");
    $getDriverName->bind_param("s", $driverId);
    $getDriverName->execute();
    $driverResult = $getDriverName->get_result();
    
    if ($driverResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Driver not found"]);
        exit;
    }
    
    $driverName = $driverResult->fetch_assoc()['name'];
    $getDriverName->close();
    
    // Count total completed trips for this driver
    $totalCompletedStmt = $conn->prepare("
        SELECT COUNT(*) as total_completed 
        FROM assign 
        WHERE driver = ? AND status = 'Completed' AND is_deleted = 0
    ");
    $totalCompletedStmt->bind_param("s", $driverName);
    $totalCompletedStmt->execute();
    $totalCompletedResult = $totalCompletedStmt->get_result();
    $totalCompleted = $totalCompletedResult->fetch_assoc()['total_completed'];
    $totalCompletedStmt->close();
    
    // Count completed trips for current month for this driver
    $currentMonth = date('Y-m');
    $monthlyCompletedStmt = $conn->prepare("
        SELECT COUNT(*) as monthly_completed 
        FROM assign 
        WHERE driver = ? AND status = 'Completed' AND is_deleted = 0 
        AND DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $monthlyCompletedStmt->bind_param("ss", $driverName, $currentMonth);
    $monthlyCompletedStmt->execute();
    $monthlyCompletedResult = $monthlyCompletedStmt->get_result();
    $monthlyCompleted = $monthlyCompletedResult->fetch_assoc()['monthly_completed'];
    $monthlyCompletedStmt->close();
    
    echo json_encode([
        "success" => true, 
        "stats" => [
            "total_completed" => $totalCompleted,
            "monthly_completed" => $monthlyCompleted,
            "current_month" => date('F Y') // For display purposes
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>