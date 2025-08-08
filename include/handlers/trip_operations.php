<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

$currentUser = $_SESSION['username'] ?? 'System';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $driverId = $data['driver_id'] ?? null;
            $driverName = $data['driver'] ?? null;
            
            if (empty($driverId) && !empty($driverName)) {
                $getDriverId = $conn->prepare("SELECT driver_id FROM drivers_table WHERE name = ? LIMIT 1");
                $getDriverId->bind_param("s", $driverName);
                $getDriverId->execute();
                $driverResult = $getDriverId->get_result();
                if ($driverResult->num_rows > 0) {
                    $driverId = $driverResult->fetch_assoc()['driver_id'];
                }
                $getDriverId->close();
            }
            
            $stmt = $conn->prepare("INSERT INTO assign 
                (plate_no, date, driver, driver_id, helper, dispatcher, container_no, client, 
                destination, shippine_line, consignee, size, cash_adv, status,
                last_modified_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssss",
                $data['plateNo'],
                $data['date'],
                $driverName,
                $driverId, 
                $data['helper'],
                $data['dispatcher'],
                $data['containerNo'],
                $data['client'],
                $data['destination'],
                $data['shippingLine'],
                $data['consignee'],
                $data['size'],
                $data['cashAdvance'],
                $data['status'],
                $currentUser  
            );
            $stmt->execute();

            if ($data['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE plate_no = ?");
                $updateTruck->bind_param("s", $data['plateNo']);
                $updateTruck->execute();
            }
            
            echo json_encode(['success' => true]);
            break;

      case 'edit':
    $getCurrent = $conn->prepare("SELECT status, plate_no FROM assign WHERE trip_id = ?");
    $getCurrent->bind_param("i", $data['id']);
    $getCurrent->execute();
    $current = $getCurrent->get_result()->fetch_assoc();

    $driverId = $data['driver_id'] ?? null;
    $driverName = $data['driver'] ?? null;
    
    if (empty($driverId) && !empty($driverName)) {
        $getDriverId = $conn->prepare("SELECT driver_id FROM drivers_table WHERE name = ? LIMIT 1");
        $getDriverId->bind_param("s", $driverName);
        $getDriverId->execute();
        $driverResult = $getDriverId->get_result();
        if ($driverResult->num_rows > 0) {
            $driverId = $driverResult->fetch_assoc()['driver_id'];
        }
        $getDriverId->close();
    }
    
    $editReasons = isset($data['editReasons']) ? json_encode($data['editReasons']) : null;
    
    $stmt = $conn->prepare("UPDATE assign SET 
        plate_no=?, date=?, driver=?, driver_id=?, helper=?, dispatcher=?, container_no=?, client=?, 
        destination=?, shippine_line=?, consignee=?, size=?, cash_adv=?, status=?,
        edit_reasons=?, last_modified_by=?, last_modified_at=NOW()
        WHERE trip_id=?");
    $stmt->bind_param("ssssssssssssssssi",
        $data['plateNo'],
        $data['date'],
        $driverName,
        $driverId,
        $data['helper'],
        $data['dispatcher'],
        $data['containerNo'],
        $data['client'],
        $data['destination'],
        $data['shippingLine'],
        $data['consignee'],
        $data['size'],
        $data['cashAdvance'],
        $data['status'],
        $editReasons,
        $currentUser,  
        $data['id']
    );
    $stmt->execute();
    
    // Update truck status based on trip status
    if ($current['status'] !== $data['status']) {
        $newTruckStatus = 'In Terminal'; // Default status
        
        if ($data['status'] === 'En Route') {
            $newTruckStatus = 'Enroute';
        } elseif ($data['status'] === 'Pending') {
            $newTruckStatus = 'In Terminal'; // Changed from 'Pending' to 'In Terminal'
        }
        
        $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE plate_no = ?");
        $updateTruck->bind_param("ss", $newTruckStatus, $data['plateNo']);
        $updateTruck->execute();
    }
    
    echo json_encode(['success' => true]);
    break;

       case 'delete':
    $getPlate = $conn->prepare("SELECT plate_no, status FROM assign WHERE trip_id = ?");
    $getPlate->bind_param("i", $data['id']);
    $getPlate->execute();
    $trip = $getPlate->get_result()->fetch_assoc();
    
    // Soft delete instead of hard delete
    $stmt = $conn->prepare("UPDATE assign SET 
        is_deleted = 1,
        delete_reason = ?,
        last_modified_by = ?,
        last_modified_at = NOW()
        WHERE trip_id = ?");
    $stmt->bind_param("ssi", 
        $data['reason'],
        $currentUser,
        $data['id']
    );
    $stmt->execute();
    
    if ($trip) {
        $newTruckStatus = 'In Terminal'; // Reset to In Terminal when trip is deleted
        $updateTruck = $conn->prepare("UPDATE truck_table SET status = ? WHERE plate_no = ?");
        $updateTruck->bind_param("ss", $newTruckStatus, $trip['plate_no']);
        $updateTruck->execute();
    }
    
    echo json_encode(['success' => true]);
    break;

        case 'get_drivers':
            $stmt = $conn->prepare("SELECT driver_id, name, email FROM drivers_table ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $drivers = [];
            while ($row = $result->fetch_assoc()) {
                $drivers[] = $row;
            }
            
            echo json_encode(['success' => true, 'drivers' => $drivers]);
            break;

        case 'get_trips_with_drivers':
            $stmt = $conn->prepare("
                SELECT a.*, d.name as driver_name, d.email as driver_email 
                FROM assign a 
                LEFT JOIN drivers_table d ON a.driver_id = d.driver_id 
                ORDER BY a.date DESC
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            echo json_encode(['success' => true, 'trips' => $trips]);
            break;

        case 'fix_missing_driver_ids':
            $stmt = $conn->prepare("
                UPDATE assign a 
                SET driver_id = (
                    SELECT d.driver_id 
                    FROM drivers_table d 
                    WHERE d.name = a.driver 
                    LIMIT 1
                ) 
                WHERE a.driver_id IS NULL AND a.driver IS NOT NULL
            ");
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            
            echo json_encode(['success' => true, 'updated_records' => $affectedRows]);
            break;

// Update the 'get_active_trips' and 'get_deleted_trips' cases to include status filtering
case 'get_active_trips':
    $statusFilter = $data['statusFilter'] ?? 'all';
    $sortOrder = $data['sortOrder'] ?? 'desc';
    $page = $data['page'] ?? 1;
    $perPage = $data['perPage'] ?? 10; // Default to 10 items per page
    
    $query = "SELECT * FROM assign WHERE is_deleted = 0";
    
    if ($statusFilter !== 'all') {
        $query .= " AND status = ?";
    }
    
    $query .= " ORDER BY date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    
    // Add pagination
    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT ? OFFSET ?";
    
    if ($statusFilter !== 'all') {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $statusFilter, $perPage, $offset);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $perPage, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM assign WHERE is_deleted = 0";
    if ($statusFilter !== 'all') {
        $countQuery .= " AND status = ?";
    }
    
    if ($statusFilter !== 'all') {
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("s", $statusFilter);
    } else {
        $countStmt = $conn->prepare($countQuery);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true, 
        'trips' => $trips,
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $page
    ]);
    break;

case 'get_deleted_trips':
    $statusFilter = $data['statusFilter'] ?? 'all';
    $sortOrder = $data['sortOrder'] ?? 'desc';
    $page = $data['page'] ?? 1;
    $perPage = $data['perPage'] ?? 10;
    
    $query = "SELECT * FROM assign WHERE is_deleted = 1";
    
    if ($statusFilter !== 'all') {
        $query .= " AND status = ?";
    }
    
    $query .= " ORDER BY date " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT ? OFFSET ?";
    
    if ($statusFilter !== 'all') {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $statusFilter, $perPage, $offset);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $perPage, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM assign WHERE is_deleted = 1";
    if ($statusFilter !== 'all') {
        $countQuery .= " AND status = ?";
    }
    
    if ($statusFilter !== 'all') {
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("s", $statusFilter);
    } else {
        $countStmt = $conn->prepare($countQuery);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true, 
        'trips' => $trips,
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $page
    ]);
    break;
    
case 'get_driver_trip_counts':
    // Get total completed trips for all drivers
    $totalQuery = "SELECT driver_id, COUNT(*) as total_completed 
                  FROM assign 
                  WHERE status = 'Completed' AND is_deleted = 0
                  GROUP BY driver_id";
    $totalResult = $conn->query($totalQuery);
    $totalCounts = [];
    while ($row = $totalResult->fetch_assoc()) {
        $totalCounts[$row['driver_id']] = $row['total_completed'];
    }
    
    // Get monthly completed trips for all drivers
    $monthlyQuery = "SELECT driver_id, COUNT(*) as monthly_completed 
                    FROM assign 
                    WHERE status = 'Completed' 
                    AND is_deleted = 0
                    AND MONTH(date) = MONTH(CURRENT_DATE())
                    AND YEAR(date) = YEAR(CURRENT_DATE())
                    GROUP BY driver_id";
    $monthlyResult = $conn->query($monthlyQuery);
    $monthlyCounts = [];
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlyCounts[$row['driver_id']] = $row['monthly_completed'];
    }
    
    // Combine results
    $allDrivers = $conn->query("SELECT driver_id FROM drivers_table");
    $tripCounts = [];
    while ($driver = $allDrivers->fetch_assoc()) {
        $driverId = $driver['driver_id'];
        $tripCounts[] = [
            'driver_id' => $driverId,
            'total_completed' => $totalCounts[$driverId] ?? 0,
            'monthly_completed' => $monthlyCounts[$driverId] ?? 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'trip_counts' => $tripCounts
    ]);
    break;


case 'restore':
    $getTrip = $conn->prepare("SELECT plate_no, status FROM assign WHERE trip_id = ?");
    $getTrip->bind_param("i", $data['id']);
    $getTrip->execute();
    $trip = $getTrip->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("UPDATE assign SET 
        is_deleted = 0,
        delete_reason = NULL,
        last_modified_by = ?,
        last_modified_at = NOW()
        WHERE trip_id = ?");
    $stmt->bind_param("si", $currentUser, $data['id']);
    $stmt->execute();
    
    // Update truck status if needed
    if ($trip && $trip['status'] === 'En Route') {
        $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Enroute' WHERE plate_no = ?");
        $updateTruck->bind_param("s", $trip['plate_no']);
        $updateTruck->execute();
    }
    
    // Get updated stats
    require 'triplogstats.php';
    $stats = getTripStatistics($conn);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    break;

    case 'get_expenses':
    $tripId = $data['tripId'] ?? 0;
    
    $stmt = $conn->prepare("SELECT expense_type, amount FROM expenses WHERE trip_id = ?");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    echo json_encode(['success' => true, 'expenses' => $expenses]);
    break;

    case 'full_delete':
    // First get the trip details
    $getTrip = $conn->prepare("SELECT * FROM assign WHERE trip_id = ?");
    $getTrip->bind_param("i", $data['id']);
    $getTrip->execute();
    $trip = $getTrip->get_result()->fetch_assoc();
    
    // Permanently delete the trip
    $stmt = $conn->prepare("DELETE FROM assign WHERE trip_id = ?");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    
    // Get updated stats
    require 'triplogstats.php';
    $stats = getTripStatistics($conn);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'message' => 'Trip permanently deleted'
    ]);
    break;

    case 'get_checklist':
    $stmt = $conn->prepare("SELECT * FROM driver_checklist WHERE trip_id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare checklist query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $data['trip_id']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute checklist query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $checklist = $result->fetch_assoc();
    $stmt->close();
    
    if ($checklist) {
        echo json_encode(['success' => true, 'checklist' => $checklist]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No checklist data found']);
    }
    break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    try { if (isset($stmt)) { @$stmt->close(); } } catch (Throwable $e) {}
    try { if (isset($getCurrent)) { @$getCurrent->close(); } } catch (Throwable $e) {}
    try { if (isset($getPlate)) { @$getPlate->close(); } } catch (Throwable $e) {}
    try { if (isset($updateTruck)) { @$updateTruck->close(); } } catch (Throwable $e) {}
    try { if (isset($getDriverId)) { @$getDriverId->close(); } } catch (Throwable $e) {}

    $conn->close();
}

?>