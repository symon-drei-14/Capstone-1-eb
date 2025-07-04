<?php
header("Content-Type: application/json");
require 'dbhandler.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? $_GET['action'] ?? '';

function validatePlateNumber($plateNo) {
    return preg_match("/^[A-Za-z]{2,3}-?\d{3,4}$/", $plateNo);
}

try {
    switch ($action) {
        case 'getTrucks':
            $stmt = $conn->prepare("SELECT truck_id, plate_no, capacity, status FROM truck_table ORDER BY truck_id");
            $stmt->execute();
            $result = $stmt->get_result();
            $trucks = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'trucks' => $trucks]);
            break;

        case 'addTruck':
            if (!validatePlateNumber($data['plate_no'])) {
                throw new Exception("Invalid plate number format. Use format like ABC123 or ABC-1234");
            }

            $stmt = $conn->prepare("INSERT INTO truck_table (plate_no, capacity, status) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $data['plate_no'], $data['capacity'], $data['status']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'updateTruck':
            if (!validatePlateNumber($data['plate_no'])) {
                throw new Exception("Invalid plate number format. Use format like ABC123 or ABC-1234");
            }

            $stmt = $conn->prepare("UPDATE truck_table SET plate_no=?, capacity=?, status=? WHERE truck_id=?");
            $stmt->bind_param("sssi", $data['plate_no'], $data['capacity'], $data['status'], $data['truck_id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'deleteTruck':
            $stmt = $conn->prepare("DELETE FROM truck_table WHERE truck_id=?");
            $stmt->bind_param("i", $data['truck_id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>