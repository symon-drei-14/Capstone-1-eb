<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

try {
    // Prepare and execute the query to get all drivers
   $stmt = $conn->prepare("SELECT driver_id, name, email, contact_no, assigned_truck_id, driver_pic, created_at, last_login, last_modified_by, last_modified_at FROM drivers_table");
    $stmt->execute();
    $result = $stmt->get_result();

    $drivers = [];
    if ($result && $result->num_rows > 0) {
         while ($row = $result->fetch_assoc()) {
            // Get trip counts for this driver
            $tripCounts = getDriverTripCounts($conn, $row['driver_id']);
            
            // Format created_at properly
            if ($row['created_at'] !== null) {
                $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
            }

            // Format last_modified_at just in case it's there
            if ($row['last_modified_at'] !== null) {
                $row['last_modified_at'] = date('Y-m-d H:i:s', strtotime($row['last_modified_at']));
            }
            
            // Format last_login properly
            if ($row['last_login'] !== null && $row['last_login'] !== 'NULL') {
                $row['last_login'] = date('Y-m-d H:i:s', strtotime($row['last_login']));
            } else {
                $row['last_login'] = 'Never';
            }
            
            // Add trip counts to driver data
            $row['total_completed'] = $tripCounts['total_completed'];
            $row['monthly_completed'] = $tripCounts['monthly_completed'];
            
            $drivers[] = $row;
        }
        
        echo json_encode(["success" => true, "drivers" => $drivers]);
    } else {
        echo json_encode(["success" => true, "drivers" => []]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

// Function to get driver trip counts
function getDriverTripCounts($conn, $driver_id) {
    $counts = [
        'total_completed' => 0,
        'monthly_completed' => 0
    ];
    
    try {
        // Get total completed trips
        $totalStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM trips t
            WHERE t.driver_id = ? 
            AND t.status = 'Completed'
            AND NOT EXISTS (
                SELECT 1 FROM audit_logs_trips alt 
                WHERE alt.trip_id = t.trip_id AND alt.is_deleted = 1
            )
        ");
        $totalStmt->bind_param("i", $driver_id);
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result();
        
        if ($totalResult && $totalRow = $totalResult->fetch_assoc()) {
            $counts['total_completed'] = $totalRow['total'];
        }
        $totalStmt->close();
        
        // Get completed trips for current month
        $currentMonth = date('Y-m');
        $monthlyStmt = $conn->prepare("
            SELECT COUNT(*) as monthly 
            FROM trips t
            WHERE t.driver_id = ? 
            AND t.status = 'Completed'
            AND DATE(t.trip_date) >= ?
            AND NOT EXISTS (
                SELECT 1 FROM audit_logs_trips alt 
                WHERE alt.trip_id = t.trip_id AND alt.is_deleted = 1
            )
        ");
        $monthStart = date('Y-m-01');
        $monthlyStmt->bind_param("is", $driver_id, $monthStart);
        $monthlyStmt->execute();
        $monthlyResult = $monthlyStmt->get_result();
        
        if ($monthlyResult && $monthlyRow = $monthlyResult->fetch_assoc()) {
            $counts['monthly_completed'] = $monthlyRow['monthly'];
        }
        $monthlyStmt->close();
        
    } catch (Exception $e) {
        // If there's an error, just return the default counts (0)
        error_log("Error getting trip counts for driver $driver_id: " . $e->getMessage());
    }
    
    return $counts;
}

$conn->close();
?>