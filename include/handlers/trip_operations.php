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
            
            if ($current['status'] !== $data['status']) {
                $newTruckStatus = 'Good'; 
                
                if ($data['status'] === 'En Route') {
                    $newTruckStatus = 'Enroute';
                } elseif ($data['status'] === 'Pending') {
                    $newTruckStatus = 'Pending';
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
            
            $stmt = $conn->prepare("DELETE FROM assign WHERE trip_id=?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            
            if ($trip && $trip['status'] === 'En Route') {
                $updateTruck = $conn->prepare("UPDATE truck_table SET status = 'Good' WHERE plate_no = ?");
                $updateTruck->bind_param("s", $trip['plate_no']);
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